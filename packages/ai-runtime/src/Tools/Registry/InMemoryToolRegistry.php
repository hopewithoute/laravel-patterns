<?php

namespace Labtime\AiRuntime\Tools\Registry;

use Labtime\AiRuntime\Foundation\State\RuntimeState;

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

    public function availableFor(RuntimeState $state): array
    {
        if ($state->isRejected()) {
            return [];
        }

        return array_values(array_filter(
            $this->enabled(),
            fn (ToolDefinition $definition): bool => $this->isAvailableForState($definition, $state),
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

    private function isAvailableForState(ToolDefinition $definition, RuntimeState $state): bool
    {
        $access = $definition->access;

        if (($access['requires_member'] ?? false) && ! ($state->context->tenant->attributes['is_member'] ?? false)) {
            return false;
        }

        $roles = is_array($access['roles'] ?? null) ? $access['roles'] : [];

        if ($roles !== [] && ! in_array(strtolower($state->context->actor->role), $this->normalizeStrings($roles), true)) {
            return false;
        }

        $intents = is_array($access['intents'] ?? null) ? $access['intents'] : [];

        if ($intents !== [] && ! in_array($state->intent?->value, $this->normalizeStrings($intents), true)) {
            return false;
        }

        if ($definition->capability === null) {
            return true;
        }

        return in_array($definition->capability, $state->allowedCapabilities, true);
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    private function normalizeStrings(array $values): array
    {
        return array_values(array_filter(
            array_map(
                fn (mixed $value): ?string => is_string($value) && $value !== '' ? strtolower($value) : null,
                $values,
            ),
        ));
    }
}
