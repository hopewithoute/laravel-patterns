<?php

namespace App\AI\Runtime\Streaming;

use App\AI\Runtime\Contracts\AiStreamOutput;
use App\AI\Runtime\Contracts\AiStreamSink;
use Closure;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

readonly class SseAiStreamOutput implements AiStreamOutput
{
    public function __construct(
        private AiStreamEnvelopeFactory $envelopeFactory,
        private bool $debugMode = false,
        private bool $flushOutput = true,
    ) {}

    /**
     * @param  Closure(AiStreamSink): void  $producer
     * @param  array<string, mixed>  $metadata
     */
    public function respond(Closure $producer, array $metadata = []): StreamedResponse
    {
        return response()->stream(function () use ($producer): void {
            $sink = new SseAiStreamSink(
                envelopeFactory: $this->envelopeFactory,
                debugMode: $this->debugMode,
                flushOutput: $this->flushOutput,
            );
            $sink->writePadding();

            try {
                $producer($sink);
            } catch (Throwable $exception) {
                report($exception);
                $sink->error($exception);
            } finally {
                $sink->close();
            }
        }, Response::HTTP_OK, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}

final class SseAiStreamSink implements AiStreamSink
{
    public function __construct(
        private AiStreamEnvelopeFactory $envelopeFactory,
        private bool $debugMode = false,
        private bool $flushOutput = true,
    ) {}

    public function publish(array $payload): void
    {
        echo 'data: '.json_encode($payload, JSON_THROW_ON_ERROR)."\n\n";
        $this->flush();
    }

    public function error(Throwable $exception): void
    {
        $this->publish($this->envelopeFactory->error($exception, $this->debugMode));
    }

    public function close(): void
    {
        echo "data: [DONE]\n\n";
        $this->flush();
    }

    public function writePadding(): void
    {
        echo ': '.str_repeat(' ', 2048)."\n\n";
        $this->flush();
    }

    private function flush(): void
    {
        if (! $this->flushOutput) {
            return;
        }

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
