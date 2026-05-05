<?php

namespace Tests\Feature\Api\Refactor;

use App\Actions\CommentUpdateAction;
use App\Actions\OrganizationUpdateAction;
use App\Data\CommentData;
use App\Data\OrganizationData;
use App\Models\Comment;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DtoPatternTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_update_action_uses_dto(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $task = Task::factory()->create(['organization_id' => $organization->id]);
        $comment = Comment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'content' => 'Original content',
        ]);

        $data = CommentData::from([
            'task_id' => $task->id,
            'organization_id' => $organization->id,
            'content' => 'Updated via DTO',
        ]);

        $action = app(CommentUpdateAction::class);
        $updated = $action->execute($data, $comment);

        $this->assertEquals('Updated via DTO', $updated->content);
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated via DTO',
        ]);
    }

    public function test_organization_data_dto(): void
    {
        $data = OrganizationData::from([
            'name' => 'Test Org',
            'description' => 'Test description',
        ]);

        $this->assertEquals('Test Org', $data->name);
        $this->assertEquals('Test description', $data->description);
        $this->assertTrue($data->is_active);
    }

    public function test_organization_update_action_uses_dto(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Original Name',
        ]);

        $data = OrganizationData::from([
            'name' => 'Updated via DTO',
            'description' => 'New description',
        ]);

        $action = app(OrganizationUpdateAction::class);
        $updated = $action->execute($data, $organization);

        $this->assertEquals('Updated via DTO', $updated->name);
        $this->assertEquals('New description', $updated->description);
    }
}
