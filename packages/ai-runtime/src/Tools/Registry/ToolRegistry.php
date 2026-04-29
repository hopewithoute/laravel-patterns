<?php

namespace Labtime\AiRuntime\Tools\Registry;

use Labtime\AiRuntime\Foundation\State\RuntimeState;

interface ToolRegistry
{
    /**
     * @return array<int, ToolDefinition>
     */
    public function all(): array;

    /**
     * @return array<int, ToolDefinition>
     */
    public function enabled(): array;

    /**
     * @return array<int, ToolDefinition>
     */
    public function availableFor(RuntimeState $state): array;

    public function find(string $name): ?ToolDefinition;

    public function findByTool(string|object $tool): ?ToolDefinition;
}
