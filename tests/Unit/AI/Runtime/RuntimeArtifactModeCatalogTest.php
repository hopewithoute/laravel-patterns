<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Artifacts\RuntimeArtifactModeCatalog;
use Tests\TestCase;

class RuntimeArtifactModeCatalogTest extends TestCase
{
    public function test_it_returns_configured_artifact_mode_options_and_values(): void
    {
        $catalog = new RuntimeArtifactModeCatalog([
            ['value' => 'auto', 'label' => 'Auto'],
            ['value' => 'task_summary', 'label' => 'Summary'],
            ['value' => 'approval_card', 'label' => 'Approval'],
        ]);

        $this->assertSame([
            ['value' => 'auto', 'label' => 'Auto'],
            ['value' => 'task_summary', 'label' => 'Summary'],
            ['value' => 'approval_card', 'label' => 'Approval'],
        ], $catalog->options());
        $this->assertSame(['auto', 'task_summary', 'approval_card'], $catalog->values());
    }

    public function test_it_filters_invalid_artifact_mode_definitions(): void
    {
        $catalog = new RuntimeArtifactModeCatalog([
            ['value' => 'auto', 'label' => 'Auto'],
            ['value' => '', 'label' => 'Broken'],
            ['label' => 'Missing value'],
        ]);

        $this->assertSame([
            ['value' => 'auto', 'label' => 'Auto'],
        ], $catalog->options());
    }
}
