<?php

namespace App\AI\Runtime\Contracts;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Preflight\PreflightDecision;

interface PreflightResolver
{
    public function resolve(AiRuntimeContext $context): PreflightDecision;
}
