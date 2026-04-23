<?php

namespace App\AI\Runtime\Tools\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class RuntimeTool
{
    /**
     * @param  array<int, string>  $requiredInputs
     */
    public function __construct(
        public string $name,
        public string $uiIdentifier,
        public string $label,
        public string $description,
        public string $whenToUse,
        public string $whenNotToUse,
        public array $requiredInputs = [],
        public ?string $outputContract = null,
        public ?string $capability = null,
        public string $operation = 'read',
        public int $maxAttempts = 1,
        public ?string $scope = null,
        public bool $enabled = true,
        public int $version = 1,
    ) {}

    public function llmDescription(): string
    {
        $requiredInputs = $this->requiredInputs === []
            ? 'No required inputs.'
            : 'Required fields: '.implode(', ', $this->requiredInputs).'.';

        $outputContract = $this->outputContract !== null && $this->outputContract !== ''
            ? "Output contract: {$this->outputContract}."
            : '';

        return trim(implode(' ', array_filter([
            $this->description,
            "Use when: {$this->whenToUse}.",
            "Do not use when: {$this->whenNotToUse}.",
            $requiredInputs,
            $outputContract,
        ])));
    }
}
