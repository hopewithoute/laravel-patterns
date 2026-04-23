<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Enums\AiIntent;
use App\AI\Runtime\Preflight\PreflightDecision;
use App\AI\Runtime\Retrieval\WorkspaceBasicRetrievalPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceBasicRetrievalPlannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_workspace_only_plan_for_workspace_lookup(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Show me the open tasks.',
        );

        $plan = app(WorkspaceBasicRetrievalPlanner::class)->plan($context, PreflightDecision::allow(
            intent: AiIntent::WorkspaceLookup,
            needsRetrieval: true,
        ));

        $this->assertTrue($plan->required);
        $this->assertSame(['workspace_db'], $plan->sources);
        $this->assertSame('basic_workspace_context', $plan->metadata['strategy']);
        $this->assertSame(5, $plan->filters['task_limit']);
    }

    public function test_it_builds_a_docs_only_plan_for_knowledge_lookup(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Summarize the runbook.',
        );

        $plan = app(WorkspaceBasicRetrievalPlanner::class)->plan($context, PreflightDecision::allow(
            intent: AiIntent::KnowledgeLookup,
            needsRetrieval: true,
        ));

        $this->assertTrue($plan->required);
        $this->assertSame(['lexical_docs'], $plan->sources);
        $this->assertSame(6, $plan->filters['lexical_limit']);
    }

    public function test_it_skips_retrieval_when_preflight_does_not_need_it(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Hello there.',
        );

        $plan = app(WorkspaceBasicRetrievalPlanner::class)->plan($context, PreflightDecision::allow(
            intent: AiIntent::WorkspaceChat,
            needsRetrieval: false,
        ));

        $this->assertFalse($plan->required);
        $this->assertSame([], $plan->sources);
    }
}
