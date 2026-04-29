<?php

namespace Labtime\AiRuntime\Observability\Replay;

use Labtime\AiRuntime\Models\RuntimeRunRecording;
use Labtime\AiRuntime\Observability\Recording\RunJournal;

readonly class RunReplayJournalResolver
{
    public function __construct(
        private RuntimeRunRecording $runRecording,
    ) {}

    public function resolve(string $sessionId, string $assistantMessageId): ?RunJournal
    {
        $recording = $this->runRecording->newQuery()
            ->where('session_id', $sessionId)
            ->where('assistant_message_id', $assistantMessageId)
            ->first();

        if (! $recording instanceof RuntimeRunRecording || ! is_array($recording->journal) || $recording->journal === []) {
            return null;
        }

        return RunJournal::fromArray($recording->journal);
    }
}
