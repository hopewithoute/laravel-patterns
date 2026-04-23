<?php

namespace App\AI\Runtime;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Contracts\AvailableToolResolver;
use App\AI\Runtime\Contracts\KnowledgeSource;
use App\AI\Runtime\Contracts\PolicyEngine;
use App\AI\Runtime\Contracts\PreflightResolver;
use App\AI\Runtime\Contracts\PromptMiddleware;
use App\AI\Runtime\Contracts\RetrievalPlanner;
use App\AI\Runtime\Contracts\ToolAccessResolver;
use App\AI\Runtime\Contracts\ToolExecutionPolicy;
use App\AI\Runtime\Enums\AiIntent;
use App\AI\Runtime\Enums\ArtifactIntent;
use App\AI\Runtime\Enums\RiskLevel;
use App\AI\Runtime\Execution\PreparedWorkspaceAssistantRun;
use App\AI\Runtime\Preflight\PreflightDecision;
use App\AI\Runtime\Retrieval\RetrievalPlan;
use App\AI\Runtime\Retrieval\RetrievalResult;
use App\AI\Runtime\Tools\GenericManagedTool;
use App\AI\Runtime\Tools\Registry\ToolDefinition;
use App\AI\Runtime\Tools\Registry\ToolRegistry;
use App\AI\Runtime\Tools\ToolExecutionJournal;
use App\Models\AiChatSession;
use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Pipeline;
use Laravel\Ai\Contracts\Tool;

readonly class WorkspaceAssistantRuntime
{
    /**
     * @param  array<int, PromptMiddleware>  $promptMiddlewares
     */
    public function __construct(
        private PreflightResolver $preflightResolver,
        private PolicyEngine $policyEngine,
        private RetrievalPlanner $retrievalPlanner,
        private KnowledgeSource $knowledgeSource,
        private AvailableToolResolver $availableToolResolver,
        private ToolAccessResolver $toolAccessResolver,
        private ToolExecutionPolicy $toolExecutionPolicy,
        private ToolRegistry $toolRegistry,
        private array $promptMiddlewares = [],
    ) {}

    public function prepare(
        User $user,
        Organization $organization,
        ?AiChatSession $session,
        string $prompt,
        ArtifactIntent $requestedArtifactMode = ArtifactIntent::Auto,
        bool $debug = false,
        ?string $provider = null,
        ?string $model = null,
    ): PreparedWorkspaceAssistantRun {
        $context = AiRuntimeContext::make(
            user: $user,
            organization: $organization,
            session: $session,
            prompt: $prompt,
            requestedArtifactMode: $requestedArtifactMode,
            debug: $debug,
            provider: $provider,
            model: $model,
        );

        $earlyDecision = $this->policyEngine->evaluate(
            $context,
            PreflightDecision::allow(
                intent: AiIntent::WorkspaceOperation,
                allowedCapabilities: ['workspace'],
                riskLevel: RiskLevel::Low,
            ),
        );

        if (! $earlyDecision->isAllowed()) {
            $decision = $earlyDecision;
        } else {
            $decision = $this->policyEngine->evaluate(
                $context,
                $this->preflightResolver->resolve($context),
            );
        }
        $retrievalPlan = $this->retrievalPlanner->plan($context, $decision);
        $retrievalResult = $this->resolveRetrievalResult($context, $retrievalPlan);
        $context = $this->enrichContextWithRetrieval($context, $retrievalPlan, $retrievalResult);
        $toolExecutionJournal = new ToolExecutionJournal;

        return new PreparedWorkspaceAssistantRun(
            context: $context,
            decision: $decision,
            instructions: $this->buildInstructions($context),
            tools: array_map(
                fn (Tool $tool) => $this->wrapManagedTool(
                    $tool,
                    $context,
                    $toolExecutionJournal,
                ),
                $this->toolAccessResolver->resolve(
                    $context,
                    $decision,
                    $this->availableToolResolver->resolve($context),
                )
            ),
            toolExecutionJournal: $toolExecutionJournal,
            retrievalPlan: $retrievalPlan,
            retrievalResult: $retrievalResult,
        );
    }

    private function wrapManagedTool(
        Tool $tool,
        AiRuntimeContext $context,
        ToolExecutionJournal $toolExecutionJournal,
    ): GenericManagedTool {
        $definition = $this->toolRegistry->findByTool($tool);

        if (! $definition instanceof ToolDefinition) {
            throw new \LogicException('Runtime tool ['.$tool::class.'] is missing a registry definition.');
        }

        return new GenericManagedTool(
            $definition,
            $tool,
            $context,
            $this->toolExecutionPolicy,
            $toolExecutionJournal,
        );
    }

    private function buildInstructions(AiRuntimeContext $context): string
    {
        $instructions = Pipeline::send('')
            ->through(array_map(
                fn (PromptMiddleware $middleware): Closure => function (string $instructions, Closure $next) use ($context, $middleware): string {
                    return $middleware->handle($context, $instructions, $next);
                },
                $this->promptMiddlewares,
            ))
            ->thenReturn();

        return trim($instructions);
    }

    private function resolveRetrievalResult(AiRuntimeContext $context, RetrievalPlan $retrievalPlan): RetrievalResult
    {
        if (! $this->knowledgeSource->supports($retrievalPlan)) {
            return RetrievalResult::empty();
        }

        return $this->knowledgeSource->retrieve($context, $retrievalPlan);
    }

    private function enrichContextWithRetrieval(
        AiRuntimeContext $context,
        RetrievalPlan $retrievalPlan,
        RetrievalResult $retrievalResult,
    ): AiRuntimeContext {
        return $context->withMetadata([
            'retrieval_plan' => [
                'required' => $retrievalPlan->required,
                'query' => $retrievalPlan->query,
                'sources' => $retrievalPlan->sources,
                'filters' => $retrievalPlan->filters,
                'metadata' => $retrievalPlan->metadata,
            ],
            'retrieval_summary' => $retrievalResult->metadata['summary'] ?? null,
            'retrieval_documents_count' => count($retrievalResult->documents),
        ]);
    }
}
