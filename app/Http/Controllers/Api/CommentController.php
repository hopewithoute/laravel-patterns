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

    public function store(CommentData $data, Task $task, CommentCreateAction $action): JsonResponse
    {
        // Set task_id and organization_id from route/header
        $data->task_id = $task->id;
        $data->organization_id = request()->header('X-Organization');

        $comment = $action->execute($data, $task, request()->user());

        return CommentResource::make($comment)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Comment $comment): CommentResource
    {
        return CommentResource::make($comment->load('user'));
    }

    public function update(CommentData $data, Comment $comment, CommentUpdateAction $action): CommentResource
    {
        // Preserve existing task_id and organization_id
        $data->task_id = $comment->task_id;
        $data->organization_id = $comment->organization_id;

        $comment = $action->execute($data, $comment);

        return CommentResource::make($comment->load('user'));
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $comment->delete();

        return response()->json(null, 204);
    }
}
