<?php

namespace Tests\Feature\Api\Resources;

use App\Http\Resources\Api\OrganizationResource;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_resource_structure(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Test Org',
            'slug' => 'test-org',
            'description' => 'A test organization',
        ]);

        $resource = OrganizationResource::make($organization)->response()->getData(true);
        $data = $resource['data'];

        $this->assertEquals($organization->id, $data['id']);
        $this->assertEquals('Test Org', $data['name']);
        $this->assertEquals('test-org', $data['slug']);
        $this->assertEquals('A test organization', $data['description']);
        $this->assertArrayHasKey('is_active', $data);
        $this->assertArrayHasKey('created_at', $data);
    }

    public function test_organization_resource_collection(): void
    {
        Organization::factory()->count(3)->create();

        $organizations = Organization::all();
        $resource = OrganizationResource::collection($organizations)->response()->getData(true);

        $this->assertArrayHasKey('data', $resource);
        $this->assertCount(3, $resource['data']);
    }
}
