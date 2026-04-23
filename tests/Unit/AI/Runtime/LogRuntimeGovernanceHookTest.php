<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Enums\AiIntent;
use App\AI\Runtime\Enums\CompletionMode;
use App\AI\Runtime\Enums\RiskLevel;
use App\AI\Runtime\Execution\RuntimeRunReport;
use App\AI\Runtime\Hooks\LogRuntimeGovernanceHook;
use App\AI\Runtime\Preflight\PreflightDecision;
use App\AI\Runtime\Retrieval\RetrievalResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogRuntimeGovernanceHookTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_governance_signals_with_runtime_retrieval_context(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: null,
            prompt: 'Show me the overdue tasks assigned to me.',
            metadata: [
                'retrieval_plan' => [
                    'required' => true,
                    'metadata' => [
                        'profile' => 'strict_workspace_lookup',
                        'intent' => 'workspace_lookup',
                        'classifier' => 'keyword_rule_based',
                        'strategy' => 'workspace_db_rule_based',
                    ],
                ],
            ],
        );

        $report = new RuntimeRunReport(
            context: $context,
            decision: PreflightDecision::allow(
                intent: AiIntent::WorkspaceLookup,
                riskLevel: RiskLevel::Medium,
                needsRetrieval: true,
                reasons: ['workspace_lookup_requested'],
            ),
            completionMode: CompletionMode::AgentStream,
            conversationId: 'conversation-001',
            retrievalResult: new RetrievalResult(
                query: 'Show me the overdue tasks assigned to me.',
                documents: [['id' => 'task-1']],
                metadata: [
                    'sources' => ['workspace_db'],
                    'source_breakdown' => [
                        'workspace_db' => [
                            'documents_count' => 1,
                            'driver' => 'database',
                        ],
                    ],
                ],
            ),
        );

        Log::spy();

        (new LogRuntimeGovernanceHook)->handle($report);

        Log::shouldHaveReceived('notice')
            ->once()
            ->with('AI runtime governance signal detected.', \Mockery::on(function (array $context): bool {
                $this->assertSame('workspace_db_rule_based', $context['retrieval_strategy']);
                $this->assertSame('workspace_db', $context['retrieval_summary']['sources'][0]);
                $this->assertSame(1, $context['retrieval_summary']['source_breakdown']['workspace_db']['documents_count']);

                return true;
            }));
    }
}
