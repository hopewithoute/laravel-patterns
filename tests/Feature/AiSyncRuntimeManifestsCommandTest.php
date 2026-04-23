<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AiSyncRuntimeManifestsCommandTest extends TestCase
{
    public function test_it_rewrites_generated_runtime_manifest_files(): void
    {
        $toolManifestPath = resource_path('js/components/ai/toolRegistryManifest.json');
        $artifactManifestPath = resource_path('js/components/ai/artifactRegistryManifest.json');

        $originalToolManifest = File::get($toolManifestPath);
        $originalArtifactManifest = File::get($artifactManifestPath);

        try {
            File::put($toolManifestPath, "[]\n");
            File::put($artifactManifestPath, "[]\n");

            $this->artisan('ai:sync-runtime-manifests')
                ->assertExitCode(0);

            $this->assertNotSame("[]\n", File::get($toolManifestPath));
            $this->assertNotSame("[]\n", File::get($artifactManifestPath));
        } finally {
            File::put($toolManifestPath, $originalToolManifest);
            File::put($artifactManifestPath, $originalArtifactManifest);
        }
    }
}
