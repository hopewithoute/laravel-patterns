<?php

namespace Tests\Unit\Actions\AI;

use App\Actions\CommentCreateAction;
use App\Actions\ProjectCreateAction;
use App\Actions\ProjectDeleteAction;
use App\Actions\ProjectUpdateAction;
use App\Actions\TaskCreateAction;
use App\Actions\TaskDeleteAction;
use App\Data\CommentData;
use App\Data\ProjectData;
use App\Data\TaskData;
use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\AiKnowledgeSource;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AiKnowledgeIngestionActionCompositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_actions_do_not_sync_project_knowledge_when_ingestion_is_disabled(): void
    {
        Queue::fake();

        [, $organization] = $this->createWorkspaceUser();

        $project = app(ProjectCreateAction::class)->execute(ProjectData::validateAndCreate([
            'id' => null,
            'organization_id' => $organization->id,
            'name' => 'Alpha Workspace',
            'description' => null,
            'color' => '#3B82F6',
            'is_active' => true,
        ]));

        $source = AiKnowledgeSource::query()
            ->where('source_type', 'project_snapshot')
            ->where('project_id', $project->id)
            ->first();

        $this->assertNull($source);
        Queue::assertNothingPushed();

        Queue::fake();

        app(ProjectUpdateAction::class)->execute(ProjectData::validateAndCreate([
            'id' => $project->id,
            'organization_id' => $organization->id,
            'name' => 'Alpha Workspace',
            'description' => 'Updated architecture brief.',
            'color' => '#3B82F6',
            'is_active' => true,
        ]), $project);

        Queue::assertNothingPushed();

        app(ProjectDeleteAction::class)->execute($project->fresh());

        $this->assertSame(0, AiKnowledgeSource::query()->count());
    }

    public function test_task_and_comment_actions_do_not_sync_task_knowledge_when_ingestion_is_disabled(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $project = Project::factory()->create([
            'organization_id' => $organization->id,
        ]);

        Queue::fake();

        $task = app(TaskCreateAction::class)->execute(TaskData::validateAndCreate([
            'id' => null,
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'title' => 'Initial task title',
            'description' => 'Initial task description',
            'status' => TaskStatus::Todo,
            'priority' => Priority::Medium,
            'due_date' => null,
            'completed_at' => null,
        ]));

        $taskSource = AiKnowledgeSource::query()
            ->where('source_type', 'task_snapshot')
            ->where('reference_uri', route('tasks.show', $task, false))
            ->first();

        $this->assertNull($taskSource);
        Queue::assertNothingPushed();

        Queue::fake();

        app(CommentCreateAction::class)->execute(CommentData::validateAndCreate([
            'id' => null,
            'organization_id' => $organization->id,
            'task_id' => $task->id,
            'content' => 'Customer approved the rollout plan.',
        ]), $task, $user);

        $this->assertNull(AiKnowledgeSource::query()
            ->where('source_type', 'task_snapshot')
            ->where('reference_uri', route('tasks.show', $task, false))
            ->first());
        Queue::assertNothingPushed();
    }

    public function test_task_delete_action_does_not_touch_knowledge_when_ingestion_is_disabled(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $project = Project::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Cleanup Project',
        ]);

        Queue::fake();

        $task = app(TaskCreateAction::class)->execute(TaskData::validateAndCreate([
            'id' => null,
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'title' => 'Task to delete',
            'description' => 'Transient task',
            'status' => TaskStatus::Todo,
            'priority' => Priority::Medium,
            'due_date' => null,
            'completed_at' => null,
        ]));

        Queue::fake();

        app(TaskDeleteAction::class)->execute($task->fresh());

        $this->assertSame(0, AiKnowledgeSource::query()->count());
        Queue::assertNothingPushed();
    }

    public function test_project_delete_action_does_not_touch_knowledge_when_ingestion_is_disabled(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $project = Project::factory()->create([
            'organization_id' => $organization->id,
        ]);

        Queue::fake();

        $task = app(TaskCreateAction::class)->execute(TaskData::validateAndCreate([
            'id' => null,
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'title' => 'Task under project cleanup',
            'description' => 'Cleanup descendant knowledge',
            'status' => TaskStatus::Todo,
            'priority' => Priority::Medium,
            'due_date' => null,
            'completed_at' => null,
        ]));

        app(ProjectDeleteAction::class)->execute($project->fresh());

        $this->assertSame(0, AiKnowledgeSource::query()->count());
    }
}
