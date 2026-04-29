<?php

namespace Labtime\AiRuntime\Observability\Recording;

use Labtime\AiRuntime\Foundation\Contracts\RunRecording;

class NullRunRecording implements RunRecording
{
    public function start(RunStartData $data): void {}

    public function setConversationId(?string $conversationId): void {}

    public function startCall(string $operation, array $meta = []): string
    {
        return '';
    }

    public function finishCall(string $callId, string $status = 'succeeded', array $meta = []): void {}

    public function recordOutput(array $payload, ?string $callId = null): void {}

    public function finish(array $meta = []): void {}

    public function journal(): RunJournal
    {
        return new RunJournal;
    }
}
