<?php

namespace Tests;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //
    /**
     * Helper to create a user within an organization.
     */
    protected function createWorkspaceUser(array $userAttributes = [], array $orgAttributes = []): array
    {
        $organization = Organization::factory()->create($orgAttributes);
        $user = User::factory()->create(array_merge([
            'organization_id' => $organization->id,
        ], $userAttributes));

        $organization->members()->attach($user->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        return [$user, $organization];
    }
}
