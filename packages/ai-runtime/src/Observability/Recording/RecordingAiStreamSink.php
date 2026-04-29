<?php

namespace Labtime\AiRuntime\Observability\Recording;

use Labtime\AiRuntime\Foundation\Contracts\AiStreamSink;
use Labtime\AiRuntime\Foundation\Contracts\RunRecording;
use Throwable;

readonly class RecordingAiStreamSink implements AiStreamSink
{
    public function __construct(
        private AiStreamSink $sink,
        private RunRecording $recording,
        private ?string $callId = null,
    ) {}

    public function publish(array $payload): void
    {
        $this->sink->publish($payload);
        $this->recording->recordOutput($payload, $this->callId);
    }

    public function error(Throwable $exception): void
    {
        $this->sink->error($exception);
    }

    public function close(): void
    {
        $this->sink->close();
    }
}
