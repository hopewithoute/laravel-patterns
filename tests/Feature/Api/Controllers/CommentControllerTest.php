<?php

namespace Tests\Feature\Api\Controllers;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Organization $organization;

    private Task $task;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->addMember($this->user, 'admin');
        $this->task = Task::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    private function headers(): array
    {
        return [
            'Authorization' => "Bearer {$this->token}",
            'X-Organization' => $this->organization->id,
        ];
    }

    public function test_can_list_comments_for_task(): void
    {
        Comment::factory()->count(3)->create([
            'task_id' => $this->task->id,
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->getJson(
            "/api/v1/tasks/{$this->task->id}/comments",
            $this->headers()
        );

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_comment(): void
    {
        $response = $this->postJson("/api/v1/tasks/{$this->task->id}/comments", [
            'content' => 'This is a test comment',
        ], $this->headers());

        $response->assertCreated()
            ->assertJsonPath('data.content', 'This is a test comment');

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a test comment',
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_show_comment(): void
    {
        $comment = Comment::factory()->create([
            'task_id' => $this->task->id,
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->getJson(
            "/api/v1/comments/{$comment->id}",
            $this->headers()
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $comment->id);
    }

    public function test_can_update_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'task_id' => $this->task->id,
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->putJson("/api/v1/comments/{$comment->id}", [
            'content' => 'Updated comment',
        ], $this->headers());

        $response->assertOk()
            ->assertJsonPath('data.content', 'Updated comment');
    }

    public function test_can_delete_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'task_id' => $this->task->id,
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/comments/{$comment->id}",
            [],
            $this->headers()
        );

        $response->assertNoContent();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_cannot_access_other_organization_comments(): void
    {
        $otherOrg = Organization::factory()->create();
        $otherTask = Task::factory()->create(['organization_id' => $otherOrg->id]);
        Comment::factory()->count(3)->create([
            'task_id' => $otherTask->id,
            'organization_id' => $otherOrg->id,
        ]);

        // Global scope filters by organization, so task not found
        $response = $this->getJson(
            "/api/v1/tasks/{$otherTask->id}/comments",
            $this->headers()
        );

        $response->assertNotFound();
    }
}
