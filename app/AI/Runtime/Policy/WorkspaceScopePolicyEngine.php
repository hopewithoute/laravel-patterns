<?php

namespace App\AI\Runtime\Policy;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Contracts\PolicyEngine;
use App\AI\Runtime\Enums\AiIntent;
use App\AI\Runtime\Preflight\PreflightDecision;

class WorkspaceScopePolicyEngine implements PolicyEngine
{
    public function evaluate(AiRuntimeContext $context, PreflightDecision $decision): PreflightDecision
    {
        if (! $context->organization->hasMember($context->user)) {
            return PreflightDecision::reject('workspace_membership_required', AiIntent::WorkspaceAccessDenied);
        }

        return new PreflightDecision(
            intent: $decision->intent,
            riskLevel: $decision->riskLevel,
            status: $decision->status,
            needsRetrieval: $decision->needsRetrieval,
            allowedCapabilities: $decision->allowedCapabilities,
            artifactIntent: $decision->artifactIntent,
            reasons: array_values(array_unique([
                ...$decision->reasons,
                'workspace_scope_enforced',
            ])),
            metadata: $decision->metadata,
        );
    }
}
