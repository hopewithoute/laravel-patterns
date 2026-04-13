<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_list_projects(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        Project::factory()->count(3)->create(['organization_id' => $organization->id]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->get(route('projects.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Project/Index')
            ->has('projects.data', 3)
        );
    }

    public function test_it_can_show_project_create_form(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->get(route('projects.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Project/Form')
        );
    }

    public function test_it_can_store_a_new_project(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $projectData = [
            'name' => 'New Project',
            'description' => 'Project Description',
            'color' => '#ff0000',
            'is_active' => true,
        ];

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->post(route('projects.store'), $projectData);

        $this->assertDatabaseHas('projects', [
            'name' => 'New Project',
            'organization_id' => $organization->id,
        ]);

        $project = Project::where('name', 'New Project')->first();
        $response->assertRedirect(route('projects.show', $project));
    }

    public function test_it_can_display_project_details_with_task_counts(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        // Create tasks with different statuses
        Task::factory()->create(['project_id' => $project->id, 'organization_id' => $organization->id, 'status' => TaskStatus::Done]);
        Task::factory()->create(['project_id' => $project->id, 'organization_id' => $organization->id, 'status' => TaskStatus::InProgress]);
        Task::factory()->create(['project_id' => $project->id, 'organization_id' => $organization->id, 'status' => TaskStatus::Todo]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->get(route('projects.show', $project));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Project/Show')
            ->where('project.id', $project->id)
            ->where('project.done_tasks_count', 1)
            ->where('project.in_progress_tasks_count', 1)
            ->where('project.todo_tasks_count', 1)
            ->has('tasks.data')
        );
    }

    public function test_it_can_show_edit_form(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->get(route('projects.edit', $project));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Project/Form')
            ->where('project.id', $project->id)
        );
    }

    public function test_it_can_update_a_project(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $updateData = [
            'name' => 'Updated Project Name',
            'description' => 'Updated Description',
            'color' => '#00ff00',
            'is_active' => true,
        ];

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->put(route('projects.update', $project), $updateData);

        $response->assertRedirect(route('projects.show', $project));
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project Name',
        ]);
    }

    public function test_it_can_delete_a_project(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->from(route('projects.index'))
            ->delete(route('projects.destroy', $project));

        $response->assertRedirect(route('projects.index'));
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }
}
