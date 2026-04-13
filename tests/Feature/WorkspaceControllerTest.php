<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorkspaceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_show_workspace_selection_page(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $secondOrg = Organization::factory()->create(['name' => 'Second Workspace']);
        $secondOrg->addMember($user, 'member');

        $response = $this
            ->actingAs($user)
            ->get(route('workspace.select'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Workspace/Select')
            ->has('organizations', 2)
            ->where('organizations.0.name', $organization->name)
            ->where('organizations.1.name', 'Second Workspace')
        );
    }

    public function test_it_can_set_active_workspace(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $response = $this
            ->actingAs($user)
            ->post(route('workspace.set'), [
                'organization_id' => $organization->id,
            ]);

        $response->assertRedirect(route('dashboard.index'));
        $this->assertEquals($organization->id, session('organization_id'));
    }

    public function test_it_can_show_workspace_creation_form(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('workspace.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Workspace/Create')
        );
    }

    public function test_it_can_store_new_workspace(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('workspace.store'), [
                'name' => 'New Team',
                'description' => 'A new workspace',
                'invite_emails' => 'test1@example.com, test2@example.com',
            ]);

        $response->assertRedirect(route('dashboard.index'));
        $this->assertDatabaseHas('organizations', [
            'name' => 'New Team',
        ]);

        $organization = Organization::where('name', 'New Team')->first();
        $this->assertTrue($organization->hasMember($user));
        $this->assertEquals('admin', $organization->getMemberRole($user));

        // Verify session is set to the new organization
        $this->assertEquals($organization->id, session('organization_id'));
    }
}
