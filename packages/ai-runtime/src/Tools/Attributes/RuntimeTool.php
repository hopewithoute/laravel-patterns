<?php

namespace Labtime\AiRuntime\Tools\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RuntimeTool
{
    public function __construct(
        public string $name,
        public string $uiIdentifier,
        public string $label,
        public string $description,
        public string $whenToUse,
        public string $whenNotToUse,
        public array $requiredInputs = [],
        public string $outputContract = '',
        public ?string $capability = null,
        public string $operation = 'read',
        public int $maxAttempts = 1,
        public ?string $scope = null,
        public array $access = [],
        public bool $enabled = true,
        public string $version = '1.0.0',
    ) {}

    public function llmDescription(): string
    {
        return "$this->description\n\nWhen to use: $this->whenToUse\nWhen not to use: $this->whenNotToUse";
    }
}
