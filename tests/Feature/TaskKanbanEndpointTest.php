<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskKanbanEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_requires_a_valid_date_range(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->getJson(route('tasks.kanban'));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_it_groups_tasks_into_date_and_no_due_date_columns(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $project = Project::create([
            'organization_id' => $organization->id,
            'name' => 'Launch',
            'description' => 'Launch work',
            'color' => '#22c55e',
            'is_active' => true,
        ]);

        $inRange = Task::create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Prepare launch checklist',
            'status' => 'Todo',
            'priority' => 'High',
            'due_date' => '2026-04-08',
        ]);

        $withoutDueDate = Task::create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Inbox triage',
            'status' => 'In Progress',
            'priority' => 'Medium',
            'due_date' => null,
        ]);

        Task::create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Future task',
            'status' => 'Todo',
            'priority' => 'Low',
            'due_date' => '2026-05-01',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->getJson(route('tasks.kanban', [
                'start_date' => '2026-04-06',
                'end_date' => '2026-04-12',
            ]));

        $response->assertOk()
            ->assertJsonPath('meta.start_date', '2026-04-06')
            ->assertJsonPath('meta.end_date', '2026-04-12')
            ->assertJsonPath('columns.0.key', 'no_due_date')
            ->assertJsonPath('columns.1.key', '2026-04-06')
            ->assertJsonPath('columns.3.key', '2026-04-08')
            ->assertJsonCount(8, 'columns')
            ->assertJsonCount(1, 'tasks_by_column.no_due_date')
            ->assertJsonCount(1, 'tasks_by_column.2026-04-08')
            ->assertJsonPath('tasks_by_column.no_due_date.0.id', $withoutDueDate->id)
            ->assertJsonPath('tasks_by_column.no_due_date.0.title', 'Inbox triage')
            ->assertJsonPath('tasks_by_column.2026-04-08.0.id', $inRange->id)
            ->assertJsonPath('tasks_by_column.2026-04-08.0.title', 'Prepare launch checklist')
            ->assertJsonMissingPath('tasks_by_column.2026-05-01');
    }

    public function test_it_applies_existing_task_filters_to_kanban_results(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $project = Project::create([
            'organization_id' => $organization->id,
            'name' => 'Operations',
            'description' => 'Ops work',
            'color' => '#06b6d4',
            'is_active' => true,
        ]);

        Task::create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Sync release notes',
            'status' => 'Todo',
            'priority' => 'High',
            'due_date' => '2026-04-09',
        ]);

        $matchingTask = Task::create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Review release notes',
            'status' => 'Review',
            'priority' => 'Urgent',
            'due_date' => '2026-04-09',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->getJson(route('tasks.kanban', [
                'start_date' => '2026-04-06',
                'end_date' => '2026-04-12',
                'filter' => [
                    'search' => 'release',
                    'status' => 'Review',
                    'priority' => 'Urgent',
                ],
            ]));

        $response->assertOk()
            ->assertJsonCount(1, 'tasks_by_column.2026-04-09')
            ->assertJsonPath('tasks_by_column.2026-04-09.0.id', $matchingTask->id)
            ->assertJsonPath('tasks_by_column.2026-04-09.0.status', 'Review')
            ->assertJsonPath('tasks_by_column.2026-04-09.0.priority', 'Urgent');
    }
}
