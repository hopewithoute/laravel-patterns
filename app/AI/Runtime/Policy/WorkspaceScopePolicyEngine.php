<?php

namespace App\AI\Runtime\Policy;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Contracts\PolicyEngine;
use App\AI\Runtime\Enums\AiIntent;
use App\AI\Runtime\Preflight\PreflightDecision;
use Illuminate\Support\Str;

class WorkspaceScopePolicyEngine implements PolicyEngine
{
    public function evaluate(AiRuntimeContext $context, PreflightDecision $decision): PreflightDecision
    {
        if (! $context->organization->hasMember($context->user)) {
            return PreflightDecision::reject('workspace_membership_required', AiIntent::WorkspaceAccessDenied);
        }

        if (in_array('task.create', $decision->allowedCapabilities, true) && ! $this->canCreateTasks($context)) {
            return PreflightDecision::reject(
                reason: 'task_create_role_required',
                intent: AiIntent::WorkspaceAccessDenied,
                metadata: [
                    ...$decision->metadata,
                    'required_capability' => 'task.create',
                    'role' => $context->organization->getMemberRole($context->user),
                ],
            );
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

    private function canCreateTasks(AiRuntimeContext $context): bool
    {
        $role = Str::lower((string) $context->organization->getMemberRole($context->user));

        return in_array($role, ['owner', 'admin', 'super admin'], true);
    }
}
