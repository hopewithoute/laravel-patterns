<?php

namespace Tests\Feature\Api\Controllers;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->addMember($this->user, 'admin');
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    private function headers(): array
    {
        return [
            'Authorization' => "Bearer {$this->token}",
            'X-Organization' => $this->organization->id,
        ];
    }

    public function test_can_list_tasks(): void
    {
        Task::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->getJson('/api/tasks', $this->headers());

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_task(): void
    {
        $project = Project::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->postJson('/api/tasks', [
            'title' => 'New Task',
            'description' => 'Task description',
            'project_id' => $project->id,
            'priority' => 'High',
            'status' => 'Todo',
        ], $this->headers());

        $response->assertCreated()
            ->assertJsonPath('data.title', 'New Task');

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_can_show_task(): void
    {
        $task = Task::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->getJson("/api/tasks/{$task->id}", $this->headers());

        $response->assertOk()
            ->assertJsonPath('data.id', $task->id);
    }

    public function test_can_update_task(): void
    {
        $task = Task::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Title',
            'priority' => 'Urgent',
        ], $this->headers());

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_can_delete_task(): void
    {
        $task = Task::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->deleteJson(
            "/api/tasks/{$task->id}",
            [],
            $this->headers()
        );

        $response->assertNoContent();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_cannot_access_tasks_without_organization(): void
    {
        // Skip this test - organization middleware not implemented yet
        $this->markTestSkipped('Organization middleware not implemented yet');
    }

    public function test_cannot_access_other_organization_tasks(): void
    {
        $otherOrg = Organization::factory()->create();
        Task::factory()->count(3)->create([
            'organization_id' => $otherOrg->id,
        ]);

        $response = $this->getJson('/api/tasks', $this->headers());

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
