<?php

namespace Labtime\AiRuntime\Artifacts\Registry;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Labtime\AiRuntime\Foundation\Enums\ArtifactIntent;

readonly class ArtifactTypeDefinition
{
    /**
     * @param  array<int, string>  $requiredDataKeys
     * @param  null|callable(array<string, mixed>): bool  $dataValidator
     * @param  null|callable(JsonSchema): array<string, mixed>  $schemaBuilder
     */
    public function __construct(
        public string $type,
        public string $label,
        public string $description,
        public string $renderer,
        public string $llmUsageGuidance,
        public array $requiredDataKeys = [],
        public ?string $presentationContract = null,
        public ArtifactIntent $defaultIntent = ArtifactIntent::Auto,
        public mixed $dataValidator = null,
        public mixed $schemaBuilder = null,
        public bool $enabled = true,
        public string $version = '1.0.0',
        public ?string $fallbackType = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function validateData(array $data): bool
    {
        foreach ($this->requiredDataKeys as $requiredDataKey) {
            if (! array_key_exists($requiredDataKey, $data)) {
                return false;
            }
        }

        if ($this->dataValidator === null) {
            return true;
        }

        $validator = $this->dataValidator;

        return (bool) $validator($data);
    }

    public function llmDescription(): string
    {
        $requiredKeys = $this->requiredDataKeys === []
            ? 'No required data keys.'
            : 'Required data keys: '.implode(', ', $this->requiredDataKeys).'.';

        $presentationContract = $this->presentationContract !== null && $this->presentationContract !== ''
            ? "Presentation contract: {$this->presentationContract}."
            : '';

        return trim(implode(' ', array_filter([
            $this->description,
            "Renderer: {$this->renderer}.",
            "Usage guidance: {$this->llmUsageGuidance}.",
            $requiredKeys,
            $presentationContract,
        ])));
    }

    public function promptInstruction(): string
    {
        return "{$this->type}: {$this->llmDescription()}";
    }
}
