<?php

namespace Tests\Unit\Actions\AI;

use App\AI\Actions\TaskCreateFromAiAction;
use App\AI\Data\CreateTaskToolData;
use App\AI\Exceptions\AiToolException;
use App\Enums\Priority;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCreateFromAiActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_task_from_human_friendly_project_assignee_and_due_date_inputs(): void
    {
        [$actor, $organization] = $this->createWorkspaceUser([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $project = Project::withoutEvents(fn () => Project::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Website Redesign',
        ]));

        $task = app(TaskCreateFromAiAction::class)->execute(
            CreateTaskToolData::validateAndCreate([
                'project' => 'Website Redesign',
                'title' => 'Redesign icon',
                'priority' => Priority::High,
                'due_date' => 'hari ini',
                'assign_to_me' => true,
            ]),
            $organization->id,
            $actor,
        );

        $this->assertSame($project->id, $task->project_id);
        $this->assertSame($actor->id, $task->assigned_to);
        $this->assertSame(now()->toDateString(), $task->due_date?->toDateString());
        $this->assertSame(Priority::High, $task->priority?->value);
    }

    public function test_it_translates_application_validation_errors_for_ai_consumers(): void
    {
        [$actor, $organization] = $this->createWorkspaceUser();
        $project = Project::withoutEvents(fn () => Project::factory()->create([
            'organization_id' => $organization->id,
        ]));
        $outsideUser = User::factory()->create();

        $this->expectException(AiToolException::class);
        $this->expectExceptionMessage('No matching workspace user was found for assignment. Use lookup_workspace_users to find the correct assignee.');

        app(TaskCreateFromAiAction::class)->execute(
            CreateTaskToolData::validateAndCreate([
                'project' => $project->name,
                'title' => 'Plan support rotation',
                'description' => null,
                'priority' => null,
                'due_date' => null,
                'assigned_to' => $outsideUser->id,
            ]),
            $organization->id,
            $actor,
        );
    }

    public function test_it_rejects_conflicting_assignee_inputs(): void
    {
        [$actor, $organization] = $this->createWorkspaceUser();
        $project = Project::withoutEvents(fn () => Project::factory()->create([
            'organization_id' => $organization->id,
        ]));

        $this->expectException(AiToolException::class);
        $this->expectExceptionMessage('Use either assign_to_me or assigned_to when creating a task, not both.');

        app(TaskCreateFromAiAction::class)->execute(
            CreateTaskToolData::validateAndCreate([
                'project' => $project->name,
                'title' => 'Plan support rotation',
                'assign_to_me' => true,
                'assigned_to' => $actor->id,
            ]),
            $organization->id,
            $actor,
        );
    }
}
