<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiLegacyRoutesRemovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_ai_run_endpoints_are_not_available(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();

        $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->post('/ai/runs', [
                'instruction' => 'Legacy endpoint should be removed.',
            ])
            ->assertNotFound();

        $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->get('/ai/runs/legacy-id')
            ->assertNotFound();
    }
}
