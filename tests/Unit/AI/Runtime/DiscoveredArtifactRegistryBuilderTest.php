<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Artifacts\Registry\DiscoveredArtifactRegistryBuilder;
use App\AI\Runtime\Enums\ArtifactIntent;
use Tests\TestCase;

class DiscoveredArtifactRegistryBuilderTest extends TestCase
{
    public function test_it_builds_artifact_definitions_from_repeatable_runtime_attributes(): void
    {
        $definitions = app(DiscoveredArtifactRegistryBuilder::class)->build();

        $this->assertGreaterThanOrEqual(10, count($definitions));

        $taskSummary = collect($definitions)->firstWhere('type', 'task_summary');
        $table = collect($definitions)->firstWhere('type', 'table');

        $this->assertNotNull($taskSummary);
        $this->assertNotNull($table);

        $this->assertSame('task-summary', $taskSummary->renderer);
        $this->assertSame(ArtifactIntent::TaskSummary, $taskSummary->defaultIntent);
        $this->assertSame(['task_id', 'project_id', 'title', 'status'], $taskSummary->requiredDataKeys);
        $this->assertTrue($taskSummary->validateData([
            'task_id' => 'task-001',
            'project_id' => 'project-001',
            'title' => 'Release checklist',
            'status' => 'open',
        ]));
        $this->assertFalse($taskSummary->validateData([
            'task_id' => 'task-001',
            'title' => 'Release checklist',
            'status' => 'open',
        ]));

        $this->assertSame('table', $table->renderer);
        $this->assertSame(ArtifactIntent::Auto, $table->defaultIntent);
        $this->assertTrue($table->validateData([
            'columns' => ['Task', 'Status'],
            'rows' => [['Release checklist', 'open']],
        ]));
    }
}
