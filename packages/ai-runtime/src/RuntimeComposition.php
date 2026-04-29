<?php

namespace Labtime\AiRuntime;

use Labtime\AiRuntime\Artifacts\Registry\ArtifactRegistry;
use Labtime\AiRuntime\Tools\Registry\ToolRegistry;

readonly class RuntimeComposition
{
    /**
     * @param  array<string, array<int, class-string>>  $middlewares
     * @param  array<int, class-string>  $hooks
     */
    public function __construct(
        public array $middlewares,
        public array $hooks,
        public ToolRegistry $toolRegistry,
        public ArtifactRegistry $artifactRegistry,
    ) {}

    /**
     * @return array<int, class-string>
     */
    public function middlewaresFor(string $stage): array
    {
        return array_values($this->middlewares[$stage] ?? []);
    }
}
