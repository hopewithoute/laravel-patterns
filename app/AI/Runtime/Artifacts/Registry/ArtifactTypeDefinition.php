<?php

namespace App\AI\Runtime\Artifacts\Registry;

use App\AI\Runtime\Enums\ArtifactIntent;

readonly class ArtifactTypeDefinition
{
    /**
     * @param  array<int, string>  $requiredDataKeys
     * @param  null|callable(array<string, mixed>): bool  $dataValidator
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
        public bool $enabled = true,
        public int $version = 1,
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
