<?php

namespace Labtime\AiRuntime\Tests\Unit\State;

use Labtime\AiRuntime\Foundation\Context\RuntimeActor;
use Labtime\AiRuntime\Foundation\Context\RuntimeContext;
use Labtime\AiRuntime\Foundation\Context\RuntimeTenant;
use Labtime\AiRuntime\Foundation\Enums\AiIntent;
use Labtime\AiRuntime\Foundation\Enums\ArtifactIntent;
use Labtime\AiRuntime\Foundation\State\RuntimeState;
use Labtime\AiRuntime\Retrieval\RetrievalPlan;
use Labtime\AiRuntime\Retrieval\RetrievalResult;
use Labtime\AiRuntime\Tests\Support\TestCase;

class RuntimeStateTest extends TestCase
{
    public function test_it_can_create_a_runtime_state_from_context(): void
    {
        $context = $this->makeContext();

        $state = RuntimeState::start($context);

        $this->assertSame($context, $state->context);
        $this->assertNull($state->intent);
        $this->assertFalse($state->isRejected());
        $this->assertNull($state->rejectionReason);
        $this->assertSame(ArtifactIntent::StatsCard, $state->artifactIntent);
        $this->assertSame([], $state->allowedCapabilities);
        $this->assertSame([], $state->instructions);
        $this->assertSame([], $state->availableTools);
        $this->assertNull($state->retrievalPlan);
        $this->assertNull($state->retrievalResult);
        $this->assertSame([], $state->toolResults);
        $this->assertSame([], $state->meta);
        $this->assertFalse($state->needsRetrieval());
        $this->assertFalse($state->hasInstructions());
        $this->assertSame('', $state->instructionText());
    }

    public function test_it_can_track_instruction_and_capability_transitions(): void
    {
        $state = RuntimeState::start($this->makeContext())
            ->withIntent(AiIntent::WorkspaceLookup)
            ->withAllowedCapabilities(['workspace.read', ' workspace.read ', 'task.create'])
            ->appendInstructions('  First instruction  ', '', 'Second instruction')
            ->appendInstructions('Second instruction', 'Third instruction');

        $this->assertSame(AiIntent::WorkspaceLookup, $state->intent);
        $this->assertSame(['workspace.read', 'task.create'], $state->allowedCapabilities);
        $this->assertTrue($state->hasInstructions());
        $this->assertSame(['First instruction', 'Second instruction', 'Third instruction'], $state->instructions);
        $this->assertSame("First instruction\n\nSecond instruction\n\nThird instruction", $state->instructionText());
    }

    public function test_it_can_record_rejection_retrieval_and_meta_changes(): void
    {
        $retrievalPlan = new RetrievalPlan(
            required: true,
            query: 'pending tasks',
            sources: ['workspace'],
            metadata: ['strategy' => 'keyword'],
        );
        $retrievalResult = new RetrievalResult(
            query: 'pending tasks',
            documents: [
                ['id' => 'doc-1', 'title' => 'Pending Tasks'],
            ],
            metadata: ['sources' => ['workspace']],
        );

        $state = RuntimeState::start($this->makeContext())
            ->withRetrievalPlan($retrievalPlan)
            ->withRetrievalResult($retrievalResult)
            ->appendAvailableTools(['search', 'summarize'])
            ->appendToolResults([['tool' => 'search']])
            ->withMeta(['stage' => 'retrieval'])
            ->reject('Outside workspace scope', AiIntent::OutOfScope);

        $this->assertTrue($state->isRejected());
        $this->assertSame('Outside workspace scope', $state->rejectionReason);
        $this->assertSame(AiIntent::OutOfScope, $state->intent);
        $this->assertSame($retrievalPlan, $state->retrievalPlan);
        $this->assertSame($retrievalResult, $state->retrievalResult);
        $this->assertTrue($state->hasRetrievalResult());
        $this->assertFalse($state->needsRetrieval());
        $this->assertSame(['search', 'summarize'], $state->availableTools);
        $this->assertSame([['tool' => 'search']], $state->toolResults);
        $this->assertSame(['stage' => 'retrieval'], $state->meta);
    }

    public function test_it_normalizes_blank_rejection_reasons(): void
    {
        $state = RuntimeState::start($this->makeContext())->reject('   ');

        $this->assertTrue($state->isRejected());
        $this->assertSame('Request rejected.', $state->rejectionReason);
    }

    private function makeContext(): RuntimeContext
    {
        return new RuntimeContext(
            runtimeId: 'runtime-1',
            actor: new RuntimeActor('user-1', 'Taylor', 'admin', ['workspace.read', 'task.create']),
            tenant: new RuntimeTenant('tenant-1', 'Acme'),
            runtimeSession: null,
            prompt: 'Show me the pending tasks.',
            requestedArtifactMode: ArtifactIntent::StatsCard,
        );
    }
}
