<?php

namespace App\AI\Http;

use Closure;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

readonly class SseStreamWriter
{
    /**
     * Start an SSE stream with automatic padding and done-event framing.
     *
     * @param  Closure(self): void  $callback
     */
    public function stream(Closure $callback): StreamedResponse
    {
        return response()->stream(function () use ($callback): void {
            $this->writePadding();

            try {
                $callback($this);
            } catch (\Throwable $e) {
                report($e);
            }

            $this->writeDone();
        }, Response::HTTP_OK, $this->headers());
    }

    /**
     * Write an SSE data frame with the given payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function writePayload(array $payload): void
    {
        echo 'data: '.json_encode($payload, JSON_THROW_ON_ERROR)."\n\n";
        $this->flush();
    }

    /**
     * Write the final [DONE] sentinel.
     */
    public function writeDone(): void
    {
        echo "data: [DONE]\n\n";
        $this->flush();
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ];
    }

    /**
     * Write initial padding to force proxy buffer flush.
     */
    private function writePadding(): void
    {
        echo ': '.str_repeat(' ', 2048)."\n\n";
        $this->flush();
    }

    private function flush(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
