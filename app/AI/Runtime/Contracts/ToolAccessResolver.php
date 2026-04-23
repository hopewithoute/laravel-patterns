<?php

namespace App\AI\Runtime\Contracts;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Preflight\PreflightDecision;
use Laravel\Ai\Contracts\Tool;

interface ToolAccessResolver
{
    /**
     * @param  iterable<Tool>  $tools
     * @return array<int, Tool>
     */
    public function resolve(AiRuntimeContext $context, PreflightDecision $decision, iterable $tools): array;
}
