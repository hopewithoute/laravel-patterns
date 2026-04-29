<?php

namespace Labtime\AiRuntime\Foundation\Contracts;

use Labtime\AiRuntime\Observability\Recording\RunJournal;
use Labtime\AiRuntime\Observability\Recording\RunStartData;

interface RunRecording
{
    public function start(RunStartData $data): void;

    public function setConversationId(?string $conversationId): void;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function startCall(string $operation, array $meta = []): string;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function finishCall(string $callId, string $status = 'succeeded', array $meta = []): void;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordOutput(array $payload, ?string $callId = null): void;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function finish(array $meta = []): void;

    public function journal(): RunJournal;
}
