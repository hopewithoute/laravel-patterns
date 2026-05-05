<?php

namespace Tests\Feature\Api\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    private function headers(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_can_list_user_organizations(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org1->addMember($this->user, 'admin');
        $org2->addMember($this->user, 'member');

        $response = $this->getJson('/api/v1/organizations', $this->headers());

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_show_organization(): void
    {
        $organization = Organization::factory()->create();
        $organization->addMember($this->user, 'admin');

        $response = $this->getJson(
            "/api/v1/organizations/{$organization->slug}",
            $this->headers()
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $organization->id);
    }

    public function test_can_create_organization(): void
    {
        $response = $this->postJson('/api/v1/organizations', [
            'name' => 'New Organization',
            'description' => 'A new organization',
        ], $this->headers());

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Organization');

        $this->assertDatabaseHas('organizations', [
            'name' => 'New Organization',
        ]);
    }

    public function test_can_update_organization(): void
    {
        $organization = Organization::factory()->create();
        $organization->addMember($this->user, 'admin');

        $response = $this->putJson("/api/v1/organizations/{$organization->slug}", [
            'name' => 'Updated Name',
        ], $this->headers());

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_can_list_organization_members(): void
    {
        $organization = Organization::factory()->create();
        $organization->addMember($this->user, 'admin');

        $response = $this->getJson(
            "/api/v1/organizations/{$organization->slug}/members",
            $this->headers()
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
