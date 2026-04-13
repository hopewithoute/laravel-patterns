<?php

namespace Tests\Feature;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_list_tasks(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        Task::factory()->count(3)->create(['organization_id' => $organization->id]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->get(route('tasks.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Task/Index')
            ->has('tasks.data', 3)
            ->has('filters.statuses')
            ->has('filters.priorities')
        );
    }

    public function test_it_can_show_task_create_form(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->get(route('tasks.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Task/Form')
            ->has('options.projects')
            ->has('options.users')
        );
    }

    public function test_it_can_store_a_new_task(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $taskData = [
            'project_id' => $project->id,
            'title' => 'New Test Task',
            'description' => 'Test Description',
            'status' => TaskStatus::Todo,
            'priority' => Priority::Medium,
            'due_date' => now()->addDays(5)->toDateString(),
        ];

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->post(route('tasks.store'), $taskData);

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Test Task',
            'organization_id' => $organization->id,
            'project_id' => $project->id,
        ]);

        $task = Task::where('title', 'New Test Task')->first();
        $response->assertRedirect(route('tasks.show', $task));
    }

    public function test_it_can_display_task_details(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $task = Task::factory()->create(['organization_id' => $organization->id]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->get(route('tasks.show', $task));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Task/Show')
            ->where('task.id', $task->id)
            ->where('task.title', $task->title)
        );
    }

    public function test_it_can_show_edit_form(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $task = Task::factory()->create(['organization_id' => $organization->id]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->get(route('tasks.edit', $task));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Task/Form')
            ->where('task.id', $task->id)
            ->has('options.projects')
        );
    }

    public function test_it_can_update_a_task(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $task = Task::factory()->create(['organization_id' => $organization->id]);

        $updateData = [
            'project_id' => $task->project_id,
            'title' => 'Updated Task Title',
            'description' => 'Updated Description',
            'status' => TaskStatus::InProgress,
            'priority' => Priority::High,
            'due_date' => null,
        ];

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->put(route('tasks.update', $task), $updateData);

        $response->assertRedirect(route('tasks.show', $task));
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task Title',
            'status' => TaskStatus::InProgress,
        ]);
    }

    public function test_it_can_delete_a_task(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $task = Task::factory()->create(['organization_id' => $organization->id]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->from(route('tasks.index'))
            ->delete(route('tasks.destroy', $task));

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_it_can_mark_task_as_completed(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $task = Task::factory()->create([
            'organization_id' => $organization->id,
            'status' => TaskStatus::Todo,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->put(route('tasks.complete', $task));

        $response->assertRedirect();
        $this->assertEquals(TaskStatus::Done, $task->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_it_can_assign_task_to_user(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $otherUser = User::factory()->create(['organization_id' => $organization->id]);
        $task = Task::factory()->create(['organization_id' => $organization->id]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->put(route('tasks.assign', $task), [
                'assigned_to' => $otherUser->id,
            ]);

        $response->assertRedirect();
        $this->assertEquals($otherUser->id, $task->fresh()->assigned_to);
    }
}
