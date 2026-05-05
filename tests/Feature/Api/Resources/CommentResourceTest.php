<?php

namespace Tests\Feature\Api\Resources;

use App\Http\Resources\Api\CommentResource;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_resource_structure(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Test comment',
        ]);

        $resource = CommentResource::make($comment)->response()->getData(true);
        $data = $resource['data'];

        $this->assertEquals($comment->id, $data['id']);
        $this->assertEquals('Test comment', $data['content']);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
    }

    public function test_comment_resource_with_relationships(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $resource = CommentResource::make($comment->load('user', 'task'))
            ->response()->getData(true);

        $data = $resource['data'];

        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('task', $data);
    }

    public function test_comment_resource_without_relationships(): void
    {
        $comment = Comment::factory()->create();

        $resource = CommentResource::make($comment)->response()->getData(true);
        $data = $resource['data'];

        $this->assertArrayNotHasKey('user', $data);
        $this->assertArrayNotHasKey('task', $data);
    }

    public function test_comment_resource_collection(): void
    {
        Comment::factory()->count(3)->create();

        $comments = Comment::all();
        $resource = CommentResource::collection($comments)->response()->getData(true);

        $this->assertArrayHasKey('data', $resource);
        $this->assertCount(3, $resource['data']);
    }
}
