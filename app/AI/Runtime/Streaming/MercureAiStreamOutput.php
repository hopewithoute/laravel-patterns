<?php

namespace App\AI\Runtime\Streaming;

use App\AI\Runtime\Contracts\AiStreamOutput;
use App\AI\Runtime\Contracts\AiStreamSink;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class MercureAiStreamOutput implements AiStreamOutput
{
    public function __construct(
        private AiStreamEnvelopeFactory $envelopeFactory,
        private ?string $hubUrl,
        private ?string $jwt,
        private string $topicPrefix = 'ai-runtime',
        private bool $debugMode = false,
    ) {}

    /**
     * @param  Closure(AiStreamSink): void  $producer
     * @param  array<string, mixed>  $metadata
     */
    public function respond(Closure $producer, array $metadata = []): JsonResponse
    {
        $sessionId = $this->sessionIdFor($metadata);
        $topic = $this->topicFor($sessionId);
        $sink = new MercureAiStreamSink(
            envelopeFactory: $this->envelopeFactory,
            hubUrl: $this->hubUrl,
            jwt: $this->jwt,
            topic: $topic,
            debugMode: $this->debugMode,
        );

        try {
            $producer($sink);
        } catch (Throwable $exception) {
            report($exception);
            $sink->error($exception);
        } finally {
            $sink->close();
        }

        return response()->json(
            AiStreamSubscriptionDescriptor::mercure(
                sessionId: $sessionId,
                topic: $topic,
                hubUrl: $this->hubUrl,
            )->toArray(),
            Response::HTTP_ACCEPTED,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function sessionIdFor(array $metadata): string
    {
        $sessionId = is_string($metadata['session_id'] ?? null) ? $metadata['session_id'] : null;

        return $sessionId ?: Str::uuid()->toString();
    }

    private function topicFor(string $sessionId): string
    {
        return trim($this->topicPrefix, '/').'/'.$sessionId;
    }
}

final class MercureAiStreamSink implements AiStreamSink
{
    public function __construct(
        private readonly AiStreamEnvelopeFactory $envelopeFactory,
        private readonly ?string $hubUrl,
        private readonly ?string $jwt,
        private readonly string $topic,
        private readonly bool $debugMode = false,
    ) {}

    public function publish(array $payload): void
    {
        if ($this->hubUrl === null || $this->hubUrl === '') {
            throw new \RuntimeException('Mercure hub URL is not configured for AI stream output.');
        }

        $request = Http::asForm();

        if ($this->jwt !== null && $this->jwt !== '') {
            $request = $request->withToken($this->jwt);
        }

        $request->post($this->hubUrl, [
            'topic' => $this->topic,
            'data' => json_encode($payload, JSON_THROW_ON_ERROR),
        ])->throw();
    }

    public function error(Throwable $exception): void
    {
        $this->publish($this->envelopeFactory->error($exception, $this->debugMode));
    }

    public function close(): void
    {
        $this->publish($this->envelopeFactory->done());
    }
}
