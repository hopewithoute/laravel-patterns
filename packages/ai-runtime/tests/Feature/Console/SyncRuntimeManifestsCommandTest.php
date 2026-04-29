<?php

namespace Labtime\AiRuntime\Tests\Feature\Console;

use Labtime\AiRuntime\Artifacts\Registry\ArtifactManifestExporter;
use Labtime\AiRuntime\Artifacts\Registry\ArtifactTypeDefinition;
use Labtime\AiRuntime\Artifacts\Registry\InMemoryArtifactRegistry;
use Labtime\AiRuntime\Console\Commands\SyncRuntimeManifestsCommand;
use Labtime\AiRuntime\Foundation\Enums\ArtifactIntent;
use Labtime\AiRuntime\Tests\Support\TestCase;
use Labtime\AiRuntime\Tools\Registry\InMemoryToolRegistry;
use Labtime\AiRuntime\Tools\Registry\ToolDefinition;
use Labtime\AiRuntime\Tools\Registry\ToolManifestExporter;
use Symfony\Component\Console\Tester\CommandTester;

class SyncRuntimeManifestsCommandTest extends TestCase
{
    public function test_it_rewrites_generated_runtime_manifest_files(): void
    {
        $toolManifestPath = sys_get_temp_dir().'/ai-runtime-tools-'.uniqid().'.json';
        $artifactManifestPath = sys_get_temp_dir().'/ai-runtime-artifacts-'.uniqid().'.json';

        $this->setConfig([
            'ai-runtime.manifests.tools_path' => $toolManifestPath,
            'ai-runtime.manifests.artifacts_path' => $artifactManifestPath,
        ]);

        $toolExporter = new ToolManifestExporter(new InMemoryToolRegistry([
            new ToolDefinition(
                name: 'dummy-tool',
                uiIdentifier: 'dummy_tool',
                label: 'Dummy Tool',
                description: 'A package-local tool fixture.',
                whenToUse: 'You need a deterministic tool for testing.',
                whenNotToUse: 'You are executing real workspace actions.',
                schemaBuilder: fn (): array => [],
            ),
        ]));
        $artifactExporter = new ArtifactManifestExporter(new InMemoryArtifactRegistry([
            new ArtifactTypeDefinition(
                type: 'dummy_card',
                label: 'Dummy Card',
                description: 'A package-local artifact fixture.',
                renderer: 'dummy-card',
                llmUsageGuidance: 'Use for package tests.',
                defaultIntent: ArtifactIntent::Update,
            ),
        ]));

        $command = new SyncRuntimeManifestsCommand($toolExporter, $artifactExporter);
        $command->setLaravel($this->container);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertJsonStringEqualsJsonString(
            json_encode($toolExporter->export(), JSON_THROW_ON_ERROR),
            (string) file_get_contents($toolManifestPath),
        );
        $this->assertJsonStringEqualsJsonString(
            json_encode($artifactExporter->export(), JSON_THROW_ON_ERROR),
            (string) file_get_contents($artifactManifestPath),
        );

        @unlink($toolManifestPath);
        @unlink($artifactManifestPath);
    }
}
