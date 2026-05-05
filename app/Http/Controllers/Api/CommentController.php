<?php

namespace App\Http\Controllers\Api;

use App\Actions\CommentCreateAction;
use App\Actions\CommentUpdateAction;
use App\Data\CommentData;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CommentResource;
use App\Models\Comment;
use App\Models\Task;
use App\QueryBuilders\CommentIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommentController extends Controller
{
    public function index(Task $task, CommentIndexQuery $query): AnonymousResourceCollection
    {
        $query->where('task_id', $task->id);

        return CommentResource::collection($query->jsonPaginate());
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
