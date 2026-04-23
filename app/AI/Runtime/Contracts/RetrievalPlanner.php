<?php

namespace App\AI\Runtime\Contracts;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Preflight\PreflightDecision;
use App\AI\Runtime\Retrieval\RetrievalPlan;

interface RetrievalPlanner
{
    public function plan(AiRuntimeContext $context, PreflightDecision $decision): RetrievalPlan;
}
