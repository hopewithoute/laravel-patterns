<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Contracts\TelemetryStore;
use App\AI\Runtime\Enums\AiIntent;
use App\AI\Runtime\Enums\CompletionMode;
use App\AI\Runtime\Execution\RuntimeRunReport;
use App\AI\Runtime\Hooks\PersistRuntimeTelemetryHook;
use App\AI\Runtime\Preflight\PreflightDecision;
use App\Models\AiRuntimeTelemetryRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PersistRuntimeTelemetryHookTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_delegates_persistence_to_the_telemetry_store_adapter(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Create a task.',
        );

        $report = new RuntimeRunReport(
            context: $context,
            decision: PreflightDecision::allow(intent: AiIntent::TaskCreate),
            completionMode: CompletionMode::AgentStream,
        );

        $store = Mockery::mock(TelemetryStore::class);
        $store->shouldReceive('store')
            ->once()
            ->with($report)
            ->andReturn(new AiRuntimeTelemetryRun(['id' => 'telemetry-run-001']));
        $store->shouldReceive('driverName')->zeroOrMoreTimes()->andReturn('database');

        (new PersistRuntimeTelemetryHook($store))->handle($report);

        $this->addToAssertionCount(1);
    }
}
