<?php

namespace Tests\Unit\Data\AI;

use App\AI\Data\AiChatPromptData;
use App\AI\Runtime\Artifacts\RuntimeArtifactModeCatalog;
use Tests\TestCase;

class AiChatPromptDataTest extends TestCase
{
    public function test_it_builds_artifact_mode_validation_from_the_runtime_catalog(): void
    {
        config()->set('ai.runtime.artifact_modes', [
            ['value' => 'auto', 'label' => 'Auto'],
            ['value' => 'task_summary', 'label' => 'Summary'],
            ['value' => 'approval_card', 'label' => 'Approval'],
        ]);
        app()->forgetInstance(RuntimeArtifactModeCatalog::class);

        $rules = AiChatPromptData::rules();

        $this->assertSame(
            ['required', 'string', 'in:auto,task_summary,approval_card'],
            $rules['artifact_mode'],
        );
    }
}
