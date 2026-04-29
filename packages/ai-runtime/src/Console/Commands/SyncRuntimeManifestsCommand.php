<?php

namespace Labtime\AiRuntime\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Labtime\AiRuntime\Artifacts\Registry\ArtifactManifestExporter;
use Labtime\AiRuntime\Tools\Registry\ToolManifestExporter;

#[Signature('ai:sync-runtime-manifests')]
#[Description('Generate frontend AI runtime manifest files from backend registry definitions')]
class SyncRuntimeManifestsCommand extends Command
{
    public function __construct(
        private readonly ToolManifestExporter $toolManifestExporter,
        private readonly ArtifactManifestExporter $artifactManifestExporter,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $toolManifestPath = config('ai-runtime.manifests.tools_path');
        $artifactManifestPath = config('ai-runtime.manifests.artifacts_path');

        if (! $toolManifestPath || ! $artifactManifestPath) {
            $this->error('AI runtime manifest paths not configured.');

            return self::FAILURE;
        }

        $this->writeManifest($toolManifestPath, $this->toolManifestExporter->export());
        $this->writeManifest($artifactManifestPath, $this->artifactManifestExporter->export());

        $this->components->info('AI runtime manifests synchronized.');
        $this->line("Tools: {$toolManifestPath}");
        $this->line("Artifacts: {$artifactManifestPath}");

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $payload
     */
    private function writeManifest(string $path, array $payload): void
    {
        File::ensureDirectoryExists(dirname($path));

        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        File::put($path, $encoded.PHP_EOL);
    }
}
