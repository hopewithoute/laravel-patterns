<?php

namespace App\AI\Runtime\Contracts;

use App\AI\Runtime\Artifacts\ArtifactPayload;
use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Preflight\PreflightDecision;
use App\AI\Runtime\Retrieval\RetrievalResult;
use App\AI\Runtime\Tools\ToolExecutionResult;

interface ArtifactResolver
{
    /**
     * @param  array<int, ToolExecutionResult>  $toolResults
     * @return array<int, ArtifactPayload>
     */
    public function resolve(
        AiRuntimeContext $context,
        PreflightDecision $decision,
        array $toolResults = [],
        ?RetrievalResult $retrievalResult = null,
        ?string $assistantText = null,
    ): array;
}
