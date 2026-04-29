<?php

namespace Labtime\AiRuntime\Tools\Registry;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

readonly class ToolDefinition
{
    /**
     * @param  callable(JsonSchema): array<string, Type>  $schemaBuilder
     * @param  array<int, string>  $requiredInputs
     * @param  array<string, mixed>  $access
     */
    public function __construct(
        public string $name,
        public string $uiIdentifier,
        public string $label,
        public string $description,
        public string $whenToUse,
        public string $whenNotToUse,
        public mixed $schemaBuilder,
        public array $requiredInputs = [],
        public ?string $outputContract = null,
        public ?string $capability = null,
        public string $operation = 'read',
        public int $maxAttempts = 1,
        public ?string $scope = null,
        public array $access = [],
        public ?string $toolClass = null,
        public bool $enabled = true,
        public string $version = '1.0.0',
    ) {}

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $builder = $this->schemaBuilder;

        return $builder($schema);
    }

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

    public function promptInstruction(): string
    {
        return "{$this->name}: {$this->llmDescription()}";
    }

    public function matches(string|object $tool): bool
    {
        $resolved = is_string($tool) ? $tool : $tool::class;
        $basename = class_basename($resolved);

        return $resolved === $this->name
            || $basename === $this->name
            || $resolved === $this->toolClass
            || ($this->toolClass !== null && $basename === class_basename($this->toolClass));
    }
}
