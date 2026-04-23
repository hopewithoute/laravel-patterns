<?php

namespace Tests\Feature;

use App\Models\AiChatSession;
use App\Models\AiRuntimeTelemetryRun;
use App\Models\AiRuntimeTelemetrySource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiRuntimeTelemetryStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_runtime_telemetry_runs_with_workspace_friendly_dimensions(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $session = AiChatSession::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        $run = AiRuntimeTelemetryRun::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'ai_chat_session_id' => $session->id,
            'conversation_id' => 'conversation-001',
            'assistant_message_id' => 'assistant-message-001',
            'intent' => 'knowledge_lookup',
            'decision' => 'allow',
            'risk_level' => 'low',
            'completion_mode' => 'agent_stream',
            'provider' => 'cliproxyapi',
            'model' => 'gpt-4.1',
            'retrieval_strategy' => 'basic_workspace_context',
            'retrieval_required' => true,
            'retrieval_documents_count' => 2,
            'retrieval_sources' => ['lexical_docs', 'vector_docs'],
            'tools_count' => 1,
            'tool_failed_count' => 0,
            'artifacts_count' => 1,
            'prompt_tokens' => 120,
            'completion_tokens' => 45,
            'total_tokens' => 165,
            'preflight_meta' => ['classifier' => 'keyword_rule_based'],
            'tool_summary' => ['count' => 1, 'failed_count' => 0],
            'retrieval_summary' => ['documents_count' => 2],
            'usage' => ['total_tokens' => 165],
            'trace' => [['stage' => 'completion', 'status' => 'completed']],
            'meta' => ['storage_version' => 2],
        ]);

        $this->assertDatabaseHas('ai_runtime_telemetry_runs', [
            'id' => $run->id,
            'organization_id' => $organization->id,
            'retrieval_strategy' => 'basic_workspace_context',
            'intent' => 'knowledge_lookup',
        ]);
        $this->assertSame(['lexical_docs', 'vector_docs'], $run->fresh()->retrieval_sources);
        $this->assertSame('basic_workspace_context', $run->fresh()->retrieval_strategy);
        $this->assertSame($session->id, $run->fresh()->chatSession?->id);
    }

    public function test_it_persists_source_breakdown_rows_for_runtime_telemetry(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $run = AiRuntimeTelemetryRun::query()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'intent' => 'hybrid_lookup',
            'decision' => 'allow',
            'risk_level' => 'medium',
            'completion_mode' => 'agent_stream',
        ]);

        $source = AiRuntimeTelemetrySource::query()->create([
            'telemetry_run_id' => $run->id,
            'organization_id' => $organization->id,
            'source_key' => 'workspace_db',
            'documents_count' => 3,
            'driver' => 'eloquent',
            'meta' => ['matched_terms' => ['review']],
        ]);

        $this->assertDatabaseHas('ai_runtime_telemetry_sources', [
            'id' => $source->id,
            'telemetry_run_id' => $run->id,
            'source_key' => 'workspace_db',
            'documents_count' => 3,
        ]);
        $this->assertSame($run->id, $source->fresh()->telemetryRun?->id);
        $this->assertSame(['matched_terms' => ['review']], $source->fresh()->meta);
    }

    public function test_it_creates_indexes_for_runtime_and_source_telemetry_queries(): void
    {
        $this->assertTrue(Schema::hasTable('ai_runtime_telemetry_runs'));
        $this->assertTrue(Schema::hasTable('ai_runtime_telemetry_sources'));

        $runIndexes = collect(Schema::getIndexes('ai_runtime_telemetry_runs'))
            ->pluck('name')
            ->all();
        $sourceIndexes = collect(Schema::getIndexes('ai_runtime_telemetry_sources'))
            ->pluck('name')
            ->all();

        $this->assertContains('ai_runtime_runs_org_created_idx', $runIndexes);
        $this->assertContains('ai_runtime_runs_org_intent_created_idx', $runIndexes);
        $this->assertContains('ai_runtime_runs_org_profile_created_idx', $runIndexes);
        $this->assertContains('ai_runtime_runs_org_decision_risk_created_idx', $runIndexes);
        $this->assertContains('ai_runtime_runs_session_created_idx', $runIndexes);
        $this->assertContains('ai_runtime_runs_conversation_created_idx', $runIndexes);

        $this->assertContains('ai_runtime_sources_run_source_unique', $sourceIndexes);
        $this->assertContains('ai_runtime_sources_org_source_created_idx', $sourceIndexes);
        $this->assertContains('ai_runtime_sources_source_driver_idx', $sourceIndexes);
    }
}
