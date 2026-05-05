<?php

namespace App\Http\Controllers\Api;

use App\Actions\CommentCreateAction;
use App\Actions\CommentUpdateAction;
use App\Data\CommentData;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CommentResource;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommentController extends Controller
{
    public function index(Request $request, Task $task): AnonymousResourceCollection
    {
        $organizationId = $request->header('X-Organization');

        $comments = $task->comments()
            ->where('organization_id', $organizationId)
            ->with('user')
            ->latest()
            ->paginate($request->input('per_page', 15));

        return CommentResource::collection($comments);
    }

    public function store(Request $request, Task $task, CommentCreateAction $action): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $validated['task_id'] = $task->id;
        $validated['organization_id'] = $request->header('X-Organization');

        $comment = $action->execute(
            CommentData::from($validated),
            $task,
            $request->user()
        );

        return CommentResource::make($comment)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Comment $comment): CommentResource
    {
        return CommentResource::make($comment->load('user'));
    }

    public function update(Request $request, Comment $comment, CommentUpdateAction $action): CommentResource
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $validated['task_id'] = $comment->task_id;
        $validated['organization_id'] = $comment->organization_id;

        $comment = $action->execute(
            CommentData::from($validated),
            $comment
        );

        return CommentResource::make($comment->load('user'));
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $comment->delete();

        return response()->json(null, 204);
    }
}
