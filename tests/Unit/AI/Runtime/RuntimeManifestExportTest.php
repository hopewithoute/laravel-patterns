<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Artifacts\Registry\ArtifactManifestExporter;
use App\AI\Runtime\Tools\Registry\ToolManifestExporter;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RuntimeManifestExportTest extends TestCase
{
    public function test_tool_manifest_export_matches_generated_json_file(): void
    {
        $expected = app(ToolManifestExporter::class)->export();
        $actual = json_decode(
            File::get(resource_path('js/components/ai/toolRegistryManifest.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame($expected, $actual);
    }

    public function test_artifact_manifest_export_matches_generated_json_file(): void
    {
        $expected = app(ArtifactManifestExporter::class)->export();
        $actual = json_decode(
            File::get(resource_path('js/components/ai/artifactRegistryManifest.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame($expected, $actual);
    }
}
