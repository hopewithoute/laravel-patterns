<?php

namespace Labtime\AiRuntime\Streaming;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Labtime\AiRuntime\Foundation\Contracts\AiStreamOutput;
use Labtime\AiRuntime\Foundation\Contracts\AiStreamSink;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RedisAiStreamOutput implements AiStreamOutput
{
    public function __construct(
        private AiStreamEnvelopeFactory $envelopeFactory,
        private ?string $connection = null,
        private string $channelPrefix = 'ai-runtime',
        private bool $debugMode = false,
    ) {}

    /**
     * @param  Closure(AiStreamSink): void  $producer
     * @param  array<string, mixed>  $metadata
     */
    public function respond(Closure $producer, array $metadata = []): JsonResponse
    {
        $sessionId = $this->sessionIdFor($metadata);
        $channel = $this->channelFor($sessionId);
        $sink = new RedisAiStreamSink(
            envelopeFactory: $this->envelopeFactory,
            channel: $channel,
            connection: $this->connection,
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
            AiStreamSubscriptionDescriptor::redis(
                sessionId: $sessionId,
                channel: $channel,
                connection: $this->connection,
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

    private function channelFor(string $sessionId): string
    {
        return trim($this->channelPrefix, ':').':'.$sessionId;
    }
}

final class RedisAiStreamSink implements AiStreamSink
{
    public function __construct(
        private readonly AiStreamEnvelopeFactory $envelopeFactory,
        private readonly string $channel,
        private readonly ?string $connection = null,
        private readonly bool $debugMode = false,
    ) {}

    public function publish(array $payload): void
    {
        $redis = $this->connection === null || $this->connection === ''
            ? Redis::connection()
            : Redis::connection($this->connection);

        $redis->publish($this->channel, json_encode($payload, JSON_THROW_ON_ERROR));
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
