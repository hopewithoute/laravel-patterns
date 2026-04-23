<?php

namespace Tests\Unit\Actions;

use App\Actions\TaskCreateForOrganizationAction;
use App\Data\TaskData;
use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskCreateForOrganizationActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_task_for_the_requested_organization(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $project = Project::withoutEvents(fn () => Project::factory()->create([
            'organization_id' => $organization->id,
        ]));
        Queue::fake();

        $task = app(TaskCreateForOrganizationAction::class)->execute(
            TaskData::validateAndCreate([
                'id' => null,
                'organization_id' => null,
                'project_id' => $project->id,
                'assigned_to' => $user->id,
                'title' => 'Prepare launch brief',
                'description' => 'Collect outstanding blockers.',
                'status' => TaskStatus::Todo,
                'priority' => Priority::High,
                'due_date' => now()->addDay()->toDateString(),
                'completed_at' => null,
            ]),
            $organization->id,
        );

        $this->assertInstanceOf(Task::class, $task);
        $this->assertSame($organization->id, $task->organization_id);
        $this->assertSame($project->id, $task->project_id);
        $this->assertSame($user->id, $task->assigned_to);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'title' => 'Prepare launch brief',
        ]);
    }

    public function test_it_rejects_projects_outside_the_requested_organization(): void
    {
        [, $organization] = $this->createWorkspaceUser();
        [, $foreignOrganization] = $this->createWorkspaceUser(['email' => 'foreign-user@example.com']);
        $foreignProject = Project::withoutEvents(fn () => Project::factory()->create([
            'organization_id' => $foreignOrganization->id,
        ]));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Selected project is not available in the active organization.');

        app(TaskCreateForOrganizationAction::class)->execute(
            TaskData::validateAndCreate([
                'id' => null,
                'organization_id' => null,
                'project_id' => $foreignProject->id,
                'assigned_to' => null,
                'title' => 'Prepare launch brief',
                'description' => null,
                'status' => TaskStatus::Todo,
                'priority' => Priority::Medium,
                'due_date' => null,
                'completed_at' => null,
            ]),
            $organization->id,
        );
    }
}
