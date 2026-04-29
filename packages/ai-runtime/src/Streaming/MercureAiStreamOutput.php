<?php

namespace Labtime\AiRuntime\Streaming;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Labtime\AiRuntime\Foundation\Contracts\AiStreamOutput;
use Labtime\AiRuntime\Foundation\Contracts\AiStreamSink;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class MercureAiStreamOutput implements AiStreamOutput
{
    public function __construct(
        private AiStreamEnvelopeFactory $envelopeFactory,
        private ?string $publishUrl,
        private ?string $subscribeUrl,
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
            publishUrl: $this->publishUrl,
            jwt: $this->jwt,
            topic: $topic,
            debugMode: $this->debugMode,
        );

        try {
            if ($metadata !== []) {
                $sink->publish($this->envelopeFactory->streamMetadata($metadata));
            }

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
                hubUrl: $this->subscribeUrl,
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
        private readonly ?string $publishUrl,
        private readonly ?string $jwt,
        private readonly string $topic,
        private readonly bool $debugMode = false,
    ) {}

    public function publish(array $payload): void
    {
        if ($this->publishUrl === null || $this->publishUrl === '') {
            throw new \RuntimeException('Mercure publish URL is not configured for AI stream output.');
        }

        $request = Http::asForm();

        if ($this->jwt !== null && $this->jwt !== '') {
            $request = $request->withToken($this->jwt);
        }

        $request->post($this->publishUrl, [
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
