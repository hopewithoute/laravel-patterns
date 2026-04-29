<?php

namespace Labtime\AiRuntime\Tests\Unit;

use Labtime\AiRuntime\Artifacts\Registry\ArtifactManifestExporter;
use Labtime\AiRuntime\Artifacts\Registry\ExplicitArtifactRegistryBuilder;
use Labtime\AiRuntime\Artifacts\Registry\InMemoryArtifactRegistry;
use Labtime\AiRuntime\Tests\Fixtures\DisabledRuntimeTool;
use Labtime\AiRuntime\Tests\Fixtures\DummyArtifactDefinition;
use Labtime\AiRuntime\Tests\Fixtures\DummyRuntimeTool;
use Labtime\AiRuntime\Tests\Support\TestCase;
use Labtime\AiRuntime\Tools\Registry\ExplicitToolRegistryBuilder;
use Labtime\AiRuntime\Tools\Registry\InMemoryToolRegistry;
use Labtime\AiRuntime\Tools\Registry\ToolManifestExporter;

class RuntimeManifestExportTest extends TestCase
{
    public function test_tool_manifest_export_returns_enabled_tool_definitions(): void
    {
        $this->container->bind(DummyRuntimeTool::class, fn (): DummyRuntimeTool => new DummyRuntimeTool);
        $this->container->bind(DisabledRuntimeTool::class, fn (): DisabledRuntimeTool => new DisabledRuntimeTool);

        $registry = new InMemoryToolRegistry(
            (new ExplicitToolRegistryBuilder($this->container))->build([
                DisabledRuntimeTool::class,
                DummyRuntimeTool::class,
            ]),
        );
        $actual = (new ToolManifestExporter($registry))->export();

        $this->assertSame([[
            'name' => 'dummy-tool',
            'uiIdentifier' => 'dummy_tool',
            'label' => 'Dummy Tool',
            'description' => 'A package-local tool fixture.',
            'whenToUse' => 'You need a deterministic tool for testing.',
            'whenNotToUse' => 'You are executing real workspace actions.',
            'requiredInputs' => ['query'],
            'outputContract' => 'Returns a dummy string payload.',
            'capability' => null,
            'operation' => 'read',
            'maxAttempts' => 1,
            'scope' => null,
            'access' => [],
            'enabled' => true,
            'version' => '1.0.0',
        ]], $actual);
    }

    public function test_artifact_manifest_export_returns_enabled_artifact_definitions(): void
    {
        $this->container->bind(DummyArtifactDefinition::class, fn (): DummyArtifactDefinition => new DummyArtifactDefinition);

        $registry = new InMemoryArtifactRegistry(
            (new ExplicitArtifactRegistryBuilder($this->container))->build([
                DummyArtifactDefinition::class,
            ]),
        );
        $actual = (new ArtifactManifestExporter($registry))->export();

        $this->assertSame([[
            'type' => 'dummy_card',
            'label' => 'Dummy Card',
            'description' => 'A package-local artifact fixture.',
            'renderer' => 'dummy-card',
            'llmUsageGuidance' => 'Use for package tests.',
            'requiredDataKeys' => ['title'],
            'presentationContract' => 'Provide a title field.',
            'defaultIntent' => 'auto',
            'enabled' => true,
            'version' => '1.0.0',
            'fallbackType' => null,
        ]], $actual);
    }
}
