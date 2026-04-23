<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Enums\AiIntent;
use App\AI\Runtime\Enums\CompletionMode;
use App\AI\Runtime\Execution\RuntimeRunReport;
use App\AI\Runtime\Preflight\PreflightDecision;
use App\AI\Runtime\Retrieval\RetrievalResult;
use App\AI\Runtime\Telemetry\DatabaseTelemetryStore;
use App\Models\AiChatSession;
use App\Models\AiRuntimeTelemetryRun;
use App\Models\AiRuntimeTelemetrySource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseTelemetryStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_runtime_runs_and_source_breakdown_rows(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $session = AiChatSession::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: $session,
            prompt: 'Summarize the release runbook.',
            provider: 'cliproxyapi',
            model: 'gpt-4.1',
            metadata: [
                'retrieval_plan' => [
                    'required' => true,
                    'metadata' => [
                        'profile' => 'knowledge_only',
                        'intent' => 'knowledge_lookup',
                        'classifier' => 'keyword_rule_based',
                        'strategy' => 'hybrid_docs_rrf',
                    ],
                ],
            ],
        );

        $report = new RuntimeRunReport(
            context: $context,
            decision: PreflightDecision::allow(
                intent: AiIntent::KnowledgeLookup,
                needsRetrieval: true,
            ),
            completionMode: CompletionMode::AgentStream,
            conversationId: 'conversation-001',
            assistantMessageId: 'assistant-message-001',
            usage: [
                'prompt_tokens' => 120,
                'completion_tokens' => 45,
                'total_tokens' => 165,
            ],
            retrievalResult: new RetrievalResult(
                query: 'Summarize the release runbook.',
                documents: [['id' => 'chunk-1'], ['id' => 'chunk-2']],
                metadata: [
                    'sources' => ['lexical_docs', 'vector_docs'],
                    'source_breakdown' => [
                        'lexical_docs' => [
                            'documents_count' => 2,
                            'driver' => 'sqlite_fts5',
                        ],
                        'vector_docs' => [
                            'documents_count' => 2,
                            'driver' => 'database',
                        ],
                    ],
                ],
            ),
        );

        $store = new DatabaseTelemetryStore(new AiRuntimeTelemetryRun, new AiRuntimeTelemetrySource);

        $run = $store->store($report);

        $this->assertNotNull($run);
        $this->assertSame('database', $store->driverName());
        $this->assertSame('hybrid_docs_rrf', $run->retrieval_strategy);
        $this->assertSame($session->id, $run->ai_chat_session_id);
        $this->assertCount(2, $run->sources);
        $this->assertSame(
            ['lexical_docs', 'vector_docs'],
            $run->retrieval_sources,
        );
        $this->assertDatabaseHas('ai_runtime_telemetry_runs', [
            'id' => $run->id,
            'conversation_id' => 'conversation-001',
            'retrieval_strategy' => 'hybrid_docs_rrf',
            'retrieval_documents_count' => 2,
        ]);
        $this->assertDatabaseHas('ai_runtime_telemetry_sources', [
            'telemetry_run_id' => $run->id,
            'source_key' => 'vector_docs',
            'documents_count' => 2,
            'driver' => 'database',
        ]);
    }
}
