<?php

namespace Tests\Unit\Actions\AI;

use App\AI\Actions\LookupWorkspaceProjectsAction;
use App\AI\Actions\LookupWorkspaceUsersAction;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceLookupActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_looks_up_projects_in_the_active_workspace(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        Project::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Website Redesign',
        ]);
        Project::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Mobile Redesign',
        ]);

        $results = app(LookupWorkspaceProjectsAction::class)->execute($organization->id, 'Website');

        $this->assertCount(1, $results);
        $this->assertSame('Website Redesign', $results[0]['project_name']);
    }

    public function test_it_looks_up_workspace_users_by_name_or_email(): void
    {
        [, $organization] = $this->createWorkspaceUser();
        $member = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
        $organization->members()->attach($member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $results = app(LookupWorkspaceUsersAction::class)->execute($organization->id, 'jane');

        $this->assertCount(1, $results);
        $this->assertSame('Jane Doe', $results[0]['user_name']);
        $this->assertSame('jane@example.com', $results[0]['email']);
    }
}
