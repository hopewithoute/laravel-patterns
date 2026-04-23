<?php

namespace App\AI\Runtime\Tools\Registry;

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

    public function find(string $name): ?ToolDefinition;

    public function findByTool(string|object $tool): ?ToolDefinition;
}
