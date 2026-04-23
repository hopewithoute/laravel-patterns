<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Enums\AiIntent;
use App\AI\Runtime\Enums\CompletionMode;
use App\AI\Runtime\Execution\RuntimeRunReport;
use App\AI\Runtime\Hooks\LogRuntimeSummaryHook;
use App\AI\Runtime\Preflight\PreflightDecision;
use App\AI\Runtime\Retrieval\RetrievalResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogRuntimeSummaryHookTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_runtime_summary_with_barebone_retrieval_context(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
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

        Log::spy();

        (new LogRuntimeSummaryHook)->handle($report);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('AI runtime run completed.', \Mockery::on(function (array $context): bool {
                $this->assertSame('hybrid_docs_rrf', $context['retrieval_strategy']);
                $this->assertSame('knowledge_lookup', $context['retrieval_summary']['intent']);
                $this->assertSame(2, $context['retrieval_summary']['documents_count']);
                $this->assertSame(2, $context['retrieval_summary']['source_breakdown']['lexical_docs']['documents_count']);

                return true;
            }));
    }
}
