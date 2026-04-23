<?php

namespace App\AI\Runtime\Tools\Registry;

readonly class InMemoryToolRegistry implements ToolRegistry
{
    /**
     * @param  array<int, ToolDefinition>  $definitions
     */
    public function __construct(
        private array $definitions = [],
    ) {}

    public function all(): array
    {
        return array_values($this->definitions);
    }

    public function enabled(): array
    {
        return array_values(array_filter(
            $this->definitions,
            fn (ToolDefinition $definition): bool => $definition->enabled,
        ));
    }

    public function find(string $name): ?ToolDefinition
    {
        foreach ($this->definitions as $definition) {
            if ($definition->matches($name)) {
                return $definition;
            }
        }

        return null;
    }

    public function findByTool(string|object $tool): ?ToolDefinition
    {
        foreach ($this->definitions as $definition) {
            if ($definition->matches($tool)) {
                return $definition;
            }
        }

        return null;
    }
}
