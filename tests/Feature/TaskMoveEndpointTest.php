<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskMoveEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_due_date_and_sort_order_when_moving_across_columns(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $project = Project::create([
            'organization_id' => $organization->id,
            'name' => 'Launch',
            'description' => 'Launch work',
            'color' => '#22c55e',
            'is_active' => true,
        ]);

        $task = Task::create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'Task to move',
            'status' => 'Todo',
            'priority' => 'High',
            'due_date' => '2026-04-06',
            'sort_order' => 0,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->patchJson(route('tasks.move', $task), [
                'due_date' => '2026-04-07',
                'sort_order' => 5,
            ]);

        $response->assertOk()
            ->assertJsonPath('task.due_date', '2026-04-07')
            ->assertJsonPath('task.sort_order', 5);

        $this->assertEquals('2026-04-07', $task->fresh()->due_date->toDateString());
        $this->assertEquals(5, $task->fresh()->sort_order);
    }

    public function test_it_clears_due_date_when_moving_to_no_due_date_column(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $project = Project::create(['organization_id' => $organization->id, 'name' => 'P1', 'is_active' => true]);

        $task = Task::create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'title' => 'T1',
            'status' => 'Todo',
            'priority' => 'Low',
            'due_date' => '2026-04-06',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->patchJson(route('tasks.move', $task), [
                'due_date' => null,
                'sort_order' => 0,
            ]);

        $response->assertOk()
            ->assertJsonPath('task.due_date', null);

        $this->assertNull($task->fresh()->due_date);
    }

    public function test_it_shifts_sibling_sort_orders_on_insert(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $project = Project::create(['organization_id' => $organization->id, 'name' => 'P1', 'is_active' => true]);

        $task1 = Task::create(['organization_id' => $organization->id, 'project_id' => $project->id, 'title' => 'T1', 'status' => 'Todo', 'priority' => 'Low', 'due_date' => '2026-04-08', 'sort_order' => 0]);
        $task2 = Task::create(['organization_id' => $organization->id, 'project_id' => $project->id, 'title' => 'T2', 'status' => 'Todo', 'priority' => 'Low', 'due_date' => '2026-04-08', 'sort_order' => 1]);

        $taskToMove = Task::create(['organization_id' => $organization->id, 'project_id' => $project->id, 'title' => 'Move Me', 'status' => 'Todo', 'priority' => 'Low', 'due_date' => '2026-04-06', 'sort_order' => 0]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->patchJson(route('tasks.move', $taskToMove), [
                'due_date' => '2026-04-08',
                'sort_order' => 1,
            ]);

        $response->assertOk();

        $this->assertEquals(0, $task1->fresh()->sort_order);
        $this->assertEquals(2, $task2->fresh()->sort_order); // Shifted from 1 to 2
        $this->assertEquals(1, $taskToMove->fresh()->sort_order);
    }
}
