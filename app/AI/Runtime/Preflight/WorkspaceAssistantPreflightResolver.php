<?php

namespace App\AI\Runtime\Preflight;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Contracts\PreflightResolver;

class WorkspaceAssistantPreflightResolver implements PreflightResolver
{
    public function __construct(
        private readonly WorkspacePromptClassifier $promptClassifier,
    ) {}

    public function resolve(AiRuntimeContext $context): PreflightDecision
    {
        return $this->promptClassifier->classify($context);
    }
}
