<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_list_team_members(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        User::factory()->count(2)->create(['organization_id' => $organization->id])->each(function ($u) use ($organization) {
            $organization->addMember($u);
        });

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->get(route('team.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Team/Index')
            ->has('members.data', 3) // including the admin
        );
    }

    public function test_it_redirects_to_workspace_select_if_no_org_selected(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('team.index'));

        $response->assertRedirect(route('workspace.select'));
    }

    public function test_it_can_invite_existing_user_to_team(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $otherUser = User::factory()->create(['email' => 'other@example.com']);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->post(route('team.invite'), [
                'email' => 'other@example.com',
            ]);

        $response->assertRedirect();
        $this->assertTrue($organization->hasMember($otherUser));
    }

    public function test_it_can_remove_user_from_team(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $member = User::factory()->create();
        $organization->addMember($member);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->delete(route('team.remove', $member));

        $response->assertRedirect();
        $this->assertFalse($organization->hasMember($member));
    }

    public function test_it_can_show_invite_code_page(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->get(route('team.invite-code'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Team/Invite')
            ->where('organization.id', $organization->id)
        );
    }

    public function test_it_can_regenerate_invite_code(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $oldCode = $organization->invite_code;

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->post(route('team.regenerate-code'));

        $response->assertRedirect();
        $organization->refresh();
        $this->assertNotEquals($oldCode, $organization->invite_code);
        $this->assertNotNull($organization->invite_code);
    }
}
