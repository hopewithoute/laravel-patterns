<?php

namespace App\AI\Runtime\Contracts;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Preflight\PreflightDecision;

interface PolicyEngine
{
    public function evaluate(AiRuntimeContext $context, PreflightDecision $decision): PreflightDecision;
}
