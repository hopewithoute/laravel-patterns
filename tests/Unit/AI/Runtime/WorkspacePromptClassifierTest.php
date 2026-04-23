<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Enums\AiIntent;
use App\AI\Runtime\Enums\PreflightStatus;
use App\AI\Runtime\Preflight\WorkspacePromptClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspacePromptClassifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_classifies_operational_prompts_as_workspace_lookup(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Show me the open tasks for the Mobile Redesign project.',
        );

        $classification = app(WorkspacePromptClassifier::class)->classify($context);

        $this->assertSame(PreflightStatus::Allow, $classification->status);
        $this->assertSame(AiIntent::WorkspaceLookup, $classification->intent);
        $this->assertTrue($classification->needsRetrieval);
        $this->assertSame(['workspace_lookup'], $classification->metadata['matched_intents']);
    }

    public function test_it_matches_overdue_workspace_prompts_without_extra_profile_rules(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Show me the overdue tasks assigned to me.',
        );

        $classification = app(WorkspacePromptClassifier::class)->classify($context);

        $this->assertSame(AiIntent::WorkspaceLookup, $classification->intent);
        $this->assertContains('assigned', $classification->metadata['matched_terms']['workspace_lookup']);
        $this->assertContains('overdue', $classification->metadata['matched_terms']['workspace_lookup']);
    }

    public function test_it_classifies_combined_workspace_and_knowledge_prompts_as_hybrid_lookup(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Summarize the runbook and show the related review tasks for Mobile Redesign.',
        );

        $classification = app(WorkspacePromptClassifier::class)->classify($context);

        $this->assertSame(AiIntent::HybridLookup, $classification->intent);
        $this->assertTrue($classification->needsRetrieval);
        $this->assertSame(['workspace_lookup', 'knowledge_lookup'], $classification->metadata['matched_intents']);
    }

    public function test_it_treats_non_retrieval_create_prompts_as_in_scope_workspace_chat(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Create a task for onboarding QA.',
        );

        $classification = app(WorkspacePromptClassifier::class)->classify($context);

        $this->assertSame(AiIntent::WorkspaceChat, $classification->intent);
        $this->assertFalse($classification->needsRetrieval);
    }

    public function test_it_does_not_treat_checklist_as_a_lookup_signal_inside_create_prompts(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Create a release checklist task.',
        );

        $classification = app(WorkspacePromptClassifier::class)->classify($context);

        $this->assertSame(AiIntent::WorkspaceChat, $classification->intent);
        $this->assertFalse($classification->needsRetrieval);
    }

    public function test_it_classifies_knowledge_prompts_as_knowledge_lookup(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Summarize the release runbook.',
        );

        $classification = app(WorkspacePromptClassifier::class)->classify($context);

        $this->assertSame(AiIntent::KnowledgeLookup, $classification->intent);
        $this->assertContains('runbook', $classification->metadata['matched_terms']['knowledge_lookup']);
    }
}
