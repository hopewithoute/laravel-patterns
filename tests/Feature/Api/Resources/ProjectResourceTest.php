<?php

namespace Tests\Feature\Api\Resources;

use App\Http\Resources\Api\ProjectResource;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_resource_structure(): void
    {
        $project = Project::factory()->create([
            'name' => 'Test Project',
            'description' => 'A test project',
        ]);

        $resource = ProjectResource::make($project)->response()->getData(true);
        $data = $resource['data'];

        $this->assertEquals($project->id, $data['id']);
        $this->assertEquals('Test Project', $data['name']);
        $this->assertEquals('A test project', $data['description']);
        $this->assertArrayHasKey('is_active', $data);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
    }

    public function test_project_resource_collection(): void
    {
        Project::factory()->count(3)->create();

        $projects = Project::all();
        $resource = ProjectResource::collection($projects)->response()->getData(true);

        $this->assertArrayHasKey('data', $resource);
        $this->assertCount(3, $resource['data']);
    }
}
