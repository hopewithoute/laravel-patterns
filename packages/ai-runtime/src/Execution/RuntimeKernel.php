<?php

namespace Labtime\AiRuntime\Execution;

use Illuminate\Contracts\Container\Container;
use Labtime\AiRuntime\Decision\RuntimeDecision;
use Labtime\AiRuntime\Execution\Hooks\CompositePostRunHook;
use Labtime\AiRuntime\Foundation\Context\RuntimeContext;
use Labtime\AiRuntime\Foundation\Contracts\RunRecording;
use Labtime\AiRuntime\Foundation\Contracts\RuntimeDefinition;
use Labtime\AiRuntime\Foundation\Contracts\TenantScopedTool;
use Labtime\AiRuntime\Foundation\Contracts\ToolExecutionPolicy;
use Labtime\AiRuntime\Foundation\Enums\AiIntent;
use Labtime\AiRuntime\Foundation\Enums\ArtifactIntent;
use Labtime\AiRuntime\Foundation\Enums\RiskLevel;
use Labtime\AiRuntime\Foundation\Enums\RuntimeDecisionStatus;
use Labtime\AiRuntime\Foundation\State\RuntimeState;
use Labtime\AiRuntime\Observability\Recording\InMemoryRunRecording;
use Labtime\AiRuntime\Observability\Recording\RunStartData;
use Labtime\AiRuntime\Execution\Pipeline\RuntimeStatePipeline;
use Labtime\AiRuntime\Foundation\Enums\RuntimeMiddlewareStage;
use Labtime\AiRuntime\Retrieval\RetrievalPlan;
use Labtime\AiRuntime\Retrieval\RetrievalResult;
use Labtime\AiRuntime\RuntimeCompositionFactory;
use Labtime\AiRuntime\Tools\GenericManagedTool;
use Labtime\AiRuntime\Tools\Registry\ToolDefinition;
use Labtime\AiRuntime\Tools\Registry\ToolPromptCatalogBuilder;
use Labtime\AiRuntime\Tools\Registry\ToolRegistry;
use Labtime\AiRuntime\Tools\ToolExecutionJournal;
use Laravel\Ai\Contracts\Tool;

readonly class RuntimeKernel
{
    public function __construct(
        private Container $container,
    ) {}

    public function prepareRun(
        RuntimeDefinition $definition,
        RuntimeContext $context,
        ?RunRecording $recording = null,
    ): PreparedRun {
        $composition = $this->container->make(RuntimeCompositionFactory::class)->resolve($definition);

        $recording ??= new InMemoryRunRecording;
        $recording->start(RunStartData::fromContext($context));

        $toolExecutionJournal = new ToolExecutionJournal;
        $compositionParameters = $this->compositionParameters($composition->toolRegistry, $toolExecutionJournal, $recording);
        $toolExecutionPolicy = $this->resolveToolExecutionPolicy($compositionParameters);
        
        $compositionParameters['toolExecutionPolicy'] = $toolExecutionPolicy;
        $pipeline = new RuntimeStatePipeline($this->container, $compositionParameters);

        $postRunHook = new CompositePostRunHook(array_map(
            fn (string $class) => $this->container->make($class),
            $composition->hooks
        ));


        $state = RuntimeState::start($context);

        foreach (RuntimeMiddlewareStage::all() as $stage) {
            $callId = $recording->startCall($stage);

            $state = $pipeline->run(
                $state,
                $composition->middlewaresFor($stage),
            );

            $this->finishStageCall($recording, $callId, $stage, $state);

            if ($state->isRejected()) {
                break;
            }
        }

        $decision = $this->decisionFromState($state);
        $retrievalPlan = $state->retrievalPlan ?? RetrievalPlan::none();
        $retrievalResult = $state->retrievalResult ?? RetrievalResult::empty();

        return new PreparedRun(
            context: $state->context,
            decision: $decision,
            instructions: $state->instructionText(),
            tools: $state->availableTools,
            toolExecutionJournal: $toolExecutionJournal,
            retrievalPlan: $retrievalPlan,
            retrievalResult: $retrievalResult,
            postRunHook: $postRunHook,
            recording: $recording,
            state: $state,
        );
    }

    private function finishStageCall(RunRecording $recording, string $callId, string $stage, RuntimeState $state): void
    {
        if ($stage === RuntimeMiddlewareStage::Decision->value) {
            $decision = $this->decisionFromState($state);
            $recording->finishCall($callId, $decision->isAllowed() ? 'succeeded' : 'failed', [
                'decision' => $decision->status->value,
                'intent' => $decision->intent->value,
                'risk_level' => $decision->riskLevel->value,
            ]);

            return;
        }

        if ($stage === RuntimeMiddlewareStage::Retrieval->value) {
            $plan = $state->retrievalPlan ?? RetrievalPlan::none();
            $result = $state->retrievalResult ?? RetrievalResult::empty();

            $recording->finishCall($callId, 'succeeded', [
                'required' => $plan->required,
                'sources' => $plan->sources,
                'documents_count' => count($result->documents),
                'strategy' => $plan->metadata['strategy'] ?? null,
            ]);

            return;
        }

        $recording->finishCall($callId, 'succeeded');
    }

    /**
     * @return array<string, mixed>
     */
    private function compositionParameters(ToolRegistry $toolRegistry, ToolExecutionJournal $journal, RunRecording $recording): array
    {
        return [
            'toolRegistry' => $toolRegistry,
            'toolPromptCatalogBuilder' => new ToolPromptCatalogBuilder($toolRegistry),
            'toolExecutionJournal' => $journal,
            'recording' => $recording,
        ];
    }

    private function resolveToolExecutionPolicy(array $compositionParameters): ToolExecutionPolicy
    {
        if (method_exists($this->container, 'resolved') && $this->container->resolved(ToolExecutionPolicy::class)) {
            return $this->container->make(ToolExecutionPolicy::class);
        }

        return $this->container->make(ToolExecutionPolicy::class, $compositionParameters);
    }

    private function decisionFromState(RuntimeState $state): RuntimeDecision
    {
        $metadata = is_array($state->meta['decision_metadata'] ?? null)
            ? $state->meta['decision_metadata']
            : [];
        $reasons = is_array($state->meta['reasons'] ?? null)
            ? array_values(array_filter($state->meta['reasons'], fn (mixed $value): bool => is_string($value) && $value !== ''))
            : [];
        $riskLevel = $this->riskLevelFromMeta($state->meta['risk_level'] ?? null);
        $needsRetrieval = (bool) ($state->meta['needs_retrieval'] ?? false);
        $intent = $state->intent ?? ($state->isRejected() ? AiIntent::OutOfScope : AiIntent::WorkspaceOperation);

        return new RuntimeDecision(
            intent: $intent,
            riskLevel: $state->isRejected() ? RiskLevel::High : $riskLevel,
            status: $state->isRejected() ? RuntimeDecisionStatus::Reject : RuntimeDecisionStatus::Allow,
            needsRetrieval: $state->isRejected() ? false : $needsRetrieval,
            allowedCapabilities: $state->isRejected() ? [] : $state->allowedCapabilities,
            artifactIntent: $state->isRejected() ? ArtifactIntent::None : $state->artifactIntent,
            reasons: $reasons !== [] ? $reasons : array_filter([$state->rejectionReason]),
            metadata: $metadata,
        );
    }

    private function riskLevelFromMeta(mixed $riskLevel): RiskLevel
    {
        if ($riskLevel instanceof RiskLevel) {
            return $riskLevel;
        }

        if (is_string($riskLevel)) {
            return RiskLevel::tryFrom($riskLevel) ?? RiskLevel::Low;
        }

        return RiskLevel::Low;
    }
}
