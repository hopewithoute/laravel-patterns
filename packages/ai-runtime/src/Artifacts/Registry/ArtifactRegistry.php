<?php

namespace Labtime\AiRuntime\Artifacts\Registry;

interface ArtifactRegistry
{
    /**
     * @return array<int, ArtifactTypeDefinition>
     */
    public function all(): array;

    /**
     * @return array<int, ArtifactTypeDefinition>
     */
    public function enabled(): array;

    public function find(string $type): ?ArtifactTypeDefinition;
}
