<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_post_a_comment_on_a_task(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $task = Task::factory()->create(['organization_id' => $organization->id]);

        $commentData = [
            'task_id' => $task->id,
            'content' => 'This is a test comment.',
        ];

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->post(route('tasks.comments.store', $task), $commentData);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'content' => 'This is a test comment.',
        ]);
    }

    public function test_it_can_delete_own_comment(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $task = Task::factory()->create(['organization_id' => $organization->id]);
        $comment = Comment::factory()->create([
            'organization_id' => $organization->id,
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->delete(route('tasks.comments.destroy', [$task, $comment]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_it_cannot_delete_others_comment(): void
    {
        [$user, $organization] = $this->createWorkspaceUser();
        $otherUser = User::factory()->create(['organization_id' => $organization->id]);
        $task = Task::factory()->create(['organization_id' => $organization->id]);
        $comment = Comment::factory()->create([
            'organization_id' => $organization->id,
            'task_id' => $task->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['organization_id' => $organization->id])
            ->delete(route('tasks.comments.destroy', [$task, $comment]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }
}
