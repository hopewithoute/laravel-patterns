<?php

namespace App\AI\Runtime\Artifacts\Attributes;

use App\AI\Runtime\Enums\ArtifactIntent;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
readonly class RuntimeArtifactType
{
    /**
     * @param  array<int, string>  $requiredDataKeys
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
        public ?string $validatorMethod = null,
        public bool $enabled = true,
        public int $version = 1,
        public ?string $fallbackType = null,
    ) {}

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
}
