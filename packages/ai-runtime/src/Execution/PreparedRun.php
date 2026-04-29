<?php

namespace Labtime\AiRuntime\Execution;

use Labtime\AiRuntime\Decision\RuntimeDecision;
use Labtime\AiRuntime\Foundation\Context\RuntimeContext;
use Labtime\AiRuntime\Foundation\Contracts\PostRunHook;
use Labtime\AiRuntime\Foundation\Contracts\RunRecording;
use Labtime\AiRuntime\Foundation\State\RuntimeState;
use Labtime\AiRuntime\Observability\Recording\NullRunRecording;
use Labtime\AiRuntime\Retrieval\RetrievalPlan;
use Labtime\AiRuntime\Retrieval\RetrievalResult;
use Labtime\AiRuntime\Tools\ToolExecutionJournal;

readonly class PreparedRun
{
    /**
     * @param  array<int, mixed>  $tools
     */
    public function __construct(
        public RuntimeContext $context,
        public RuntimeDecision $decision,
        public string $instructions,
        public array $tools = [],
        public ?ToolExecutionJournal $toolExecutionJournal = null,
        public ?RetrievalPlan $retrievalPlan = null,
        public ?RetrievalResult $retrievalResult = null,
        public ?PostRunHook $postRunHook = null,
        public RunRecording $recording = new NullRunRecording,
        public ?RuntimeState $state = null,
    ) {}
}
