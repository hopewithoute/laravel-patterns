<?php

namespace Labtime\AiRuntime\Foundation\State;

use Labtime\AiRuntime\Foundation\Context\RuntimeContext;
use Labtime\AiRuntime\Foundation\Enums\AiIntent;
use Labtime\AiRuntime\Foundation\Enums\ArtifactIntent;
use Labtime\AiRuntime\Retrieval\RetrievalPlan;
use Labtime\AiRuntime\Retrieval\RetrievalResult;

final readonly class RuntimeState
{
    /**
     * @param  list<string>  $allowedCapabilities
     * @param  list<string>  $instructions
     * @param  list<mixed>  $availableTools
     * @param  list<mixed>  $toolResults
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public RuntimeContext $context,
        public ?AiIntent $intent = null,
        public bool $rejected = false,
        public ?string $rejectionReason = null,
        public ArtifactIntent $artifactIntent = ArtifactIntent::Auto,
        public array $allowedCapabilities = [],
        public array $instructions = [],
        public array $availableTools = [],
        public ?RetrievalPlan $retrievalPlan = null,
        public ?RetrievalResult $retrievalResult = null,
        public array $toolResults = [],
        public array $meta = [],
    ) {}

    public static function start(RuntimeContext $context): self
    {
        return new self(
            context: $context,
            artifactIntent: $context->requestedArtifactMode,
        );
    }

    public function withContext(RuntimeContext $context): self
    {
        return $this->copy(['context' => $context]);
    }

    public function withIntent(AiIntent $intent): self
    {
        return $this->copy(['intent' => $intent]);
    }

    public function withArtifactIntent(ArtifactIntent $artifactIntent): self
    {
        return $this->copy(['artifactIntent' => $artifactIntent]);
    }

    /**
     * @param  list<string>  $allowedCapabilities
     */
    public function withAllowedCapabilities(array $allowedCapabilities): self
    {
        return $this->copy(['allowedCapabilities' => self::normalizeStrings($allowedCapabilities)]);
    }

    public function reject(string $rejectionReason, ?AiIntent $intent = null): self
    {
        $normalizedReason = trim($rejectionReason);

        return $this->copy([
            'intent' => $intent ?? $this->intent,
            'rejected' => true,
            'rejectionReason' => $normalizedReason !== '' ? $normalizedReason : 'Request rejected.',
        ]);
    }

    public function appendInstructions(string ...$instructions): self
    {
        return $this->copy([
            'instructions' => self::normalizeStrings([
                ...$this->instructions,
                ...$instructions,
            ]),
        ]);
    }

    /**
     * @param  list<mixed>  $availableTools
     */
    public function appendAvailableTools(array $availableTools): self
    {
        return $this->copy([
            'availableTools' => array_values([
                ...$this->availableTools,
                ...$availableTools,
            ]),
        ]);
    }

    /**
     * @param  list<mixed>  $availableTools
     */
    public function withAvailableTools(array $availableTools): self
    {
        return $this->copy(['availableTools' => array_values($availableTools)]);
    }

    public function withRetrievalPlan(?RetrievalPlan $retrievalPlan): self
    {
        return $this->copy(['retrievalPlan' => $retrievalPlan]);
    }

    public function withRetrievalResult(?RetrievalResult $retrievalResult): self
    {
        return $this->copy(['retrievalResult' => $retrievalResult]);
    }

    /**
     * @param  list<mixed>  $toolResults
     */
    public function withToolResults(array $toolResults): self
    {
        return $this->copy(['toolResults' => array_values($toolResults)]);
    }

    /**
     * @param  list<mixed>  $toolResults
     */
    public function appendToolResults(array $toolResults): self
    {
        return $this->copy([
            'toolResults' => array_values([
                ...$this->toolResults,
                ...$toolResults,
            ]),
        ]);
    }


    /**
     * @param  array<string, mixed>  $meta
     */
    public function withMeta(array $meta): self
    {
        return $this->copy(['meta' => [...$this->meta, ...$meta]]);
    }

    public function isRejected(): bool
    {
        return $this->rejected;
    }

    public function hasInstructions(): bool
    {
        return $this->instructions !== [];
    }

    public function needsRetrieval(): bool
    {
        return ($this->retrievalPlan?->required ?? false) && ! $this->hasRetrievalResult();
    }

    public function hasRetrievalResult(): bool
    {
        return $this->retrievalResult !== null;
    }

    public function instructionText(string $separator = "\n\n"): string
    {
        return implode($separator, $this->instructions);
    }

    /**
     * @param  array{
     *     context?: RuntimeContext,
     *     intent?: AiIntent|null,
     *     rejected?: bool,
     *     rejectionReason?: string|null,
     *     artifactIntent?: ArtifactIntent,
     *     allowedCapabilities?: list<string>,
     *     instructions?: list<string>,
     *     availableTools?: list<mixed>,
     *     retrievalPlan?: RetrievalPlan|null,
     *     retrievalResult?: RetrievalResult|null,
     *     toolResults?: list<mixed>,
     *     meta?: array<string, mixed>
     * }  $overrides
     */
    private function copy(array $overrides = []): self
    {
        return new self(
            context: array_key_exists('context', $overrides) ? $overrides['context'] : $this->context,
            intent: array_key_exists('intent', $overrides) ? $overrides['intent'] : $this->intent,
            rejected: array_key_exists('rejected', $overrides) ? $overrides['rejected'] : $this->rejected,
            rejectionReason: array_key_exists('rejectionReason', $overrides) ? $overrides['rejectionReason'] : $this->rejectionReason,
            artifactIntent: array_key_exists('artifactIntent', $overrides) ? $overrides['artifactIntent'] : $this->artifactIntent,
            allowedCapabilities: array_key_exists('allowedCapabilities', $overrides) ? $overrides['allowedCapabilities'] : $this->allowedCapabilities,
            instructions: array_key_exists('instructions', $overrides) ? $overrides['instructions'] : $this->instructions,
            availableTools: array_key_exists('availableTools', $overrides) ? $overrides['availableTools'] : $this->availableTools,
            retrievalPlan: array_key_exists('retrievalPlan', $overrides) ? $overrides['retrievalPlan'] : $this->retrievalPlan,
            retrievalResult: array_key_exists('retrievalResult', $overrides) ? $overrides['retrievalResult'] : $this->retrievalResult,
            toolResults: array_key_exists('toolResults', $overrides) ? $overrides['toolResults'] : $this->toolResults,
            meta: array_key_exists('meta', $overrides) ? $overrides['meta'] : $this->meta,
        );
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private static function normalizeStrings(array $values): array
    {
        $normalizedValues = [];

        foreach ($values as $value) {
            $normalizedValue = trim($value);

            if ($normalizedValue === '' || in_array($normalizedValue, $normalizedValues, true)) {
                continue;
            }

            $normalizedValues[] = $normalizedValue;
        }

        return $normalizedValues;
    }
}
