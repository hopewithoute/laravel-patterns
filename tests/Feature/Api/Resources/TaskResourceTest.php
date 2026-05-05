<?php

namespace Tests\Feature\Api\Resources;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Http\Resources\Api\TaskResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_resource_structure(): void
    {
        $task = Task::factory()->create([
            'title' => 'Test Task',
            'description' => 'Test description',
            'status' => TaskStatus::Todo,
            'priority' => Priority::High,
        ]);

        $resource = TaskResource::make($task)->response()->getData(true);

        $this->assertArrayHasKey('data', $resource);
        $data = $resource['data'];

        $this->assertEquals($task->id, $data['id']);
        $this->assertEquals('Test Task', $data['title']);
        $this->assertEquals('Test description', $data['description']);
        $this->assertEquals('Todo', $data['status']);
        $this->assertEquals('High', $data['priority']);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
    }

    public function test_task_resource_with_relationships(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $resource = TaskResource::make($task->load('assignee', 'project'))
            ->response()->getData(true);

        $data = $resource['data'];

        $this->assertArrayHasKey('assignee', $data);
        $this->assertArrayHasKey('project', $data);
    }

    public function test_task_resource_without_relationships(): void
    {
        $task = Task::factory()->create();

        $resource = TaskResource::make($task)->response()->getData(true);
        $data = $resource['data'];

        $this->assertArrayNotHasKey('assignee', $data);
        $this->assertArrayNotHasKey('project', $data);
    }

    public function test_task_resource_collection(): void
    {
        Task::factory()->count(3)->create();

        $tasks = Task::all();
        $resource = TaskResource::collection($tasks)->response()->getData(true);

        $this->assertArrayHasKey('data', $resource);
        $this->assertCount(3, $resource['data']);
    }
}
