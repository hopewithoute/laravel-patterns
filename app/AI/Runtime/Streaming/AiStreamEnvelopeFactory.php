<?php

namespace App\AI\Runtime\Streaming;

use App\AI\Runtime\Enums\AiStreamEvent;
use Throwable;

readonly class AiStreamEnvelopeFactory
{
    /**
     * @return array<string, mixed>
     */
    public function streamStart(string $provider, ?string $model): array
    {
        return [
            'type' => AiStreamEvent::StreamStart->value,
            'provider' => $provider,
            'model' => $model ?? 'provider-default',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function textDelta(string $delta): array
    {
        return [
            'type' => AiStreamEvent::TextDelta->value,
            'delta' => $delta,
        ];
    }

    /**
     * @param  array<string, mixed>  $usage
     * @return array<string, mixed>
     */
    public function streamEnd(array $usage = []): array
    {
        return [
            'type' => AiStreamEvent::StreamEnd->value,
            'usage' => $usage,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function error(Throwable $exception, bool $debugMode): array
    {
        $payload = [
            'type' => AiStreamEvent::Error->value,
            'message' => $debugMode
                ? $exception->getMessage()
                : 'Unable to stream assistant response.',
            'code' => 'ai_stream_error',
            'retryable' => false,
        ];

        if ($debugMode) {
            $payload['exception'] = $exception::class;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function done(): array
    {
        return [
            'type' => 'done',
        ];
    }
}
