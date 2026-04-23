<?php

namespace App\AI\Runtime\Execution;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Preflight\PreflightDecision;
use App\AI\Runtime\Retrieval\RetrievalPlan;
use App\AI\Runtime\Retrieval\RetrievalResult;
use App\AI\Runtime\Tools\ToolExecutionJournal;

readonly class PreparedWorkspaceAssistantRun
{
    /**
     * @param  array<int, mixed>  $tools
     */
    public function __construct(
        public AiRuntimeContext $context,
        public PreflightDecision $decision,
        public string $instructions,
        public array $tools = [],
        public ?ToolExecutionJournal $toolExecutionJournal = null,
        public ?RetrievalPlan $retrievalPlan = null,
        public ?RetrievalResult $retrievalResult = null,
    ) {}
}
