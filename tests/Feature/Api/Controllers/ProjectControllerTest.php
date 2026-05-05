<?php

namespace Tests\Feature\Api\Controllers;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
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

    public function test_can_list_projects(): void
    {
        Project::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->getJson('/api/projects', $this->headers());

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_project(): void
    {
        $response = $this->postJson('/api/projects', [
            'name' => 'New Project',
            'description' => 'A new project',
        ], $this->headers());

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Project');

        $this->assertDatabaseHas('projects', [
            'name' => 'New Project',
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_can_show_project(): void
    {
        $project = Project::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->getJson("/api/projects/{$project->id}", $this->headers());

        $response->assertOk()
            ->assertJsonPath('data.id', $project->id);
    }

    public function test_can_update_project(): void
    {
        $project = Project::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->putJson("/api/projects/{$project->id}", [
            'name' => 'Updated Name',
        ], $this->headers());

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_project(): void
    {
        $project = Project::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->deleteJson(
            "/api/projects/{$project->id}",
            [],
            $this->headers()
        );

        $response->assertNoContent();
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_cannot_access_other_organization_projects(): void
    {
        $otherOrg = Organization::factory()->create();
        Project::factory()->count(3)->create([
            'organization_id' => $otherOrg->id,
        ]);

        $response = $this->getJson('/api/projects', $this->headers());

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
