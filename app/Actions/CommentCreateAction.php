<?php

namespace App\Actions;

use App\Data\CommentData;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Action to create a new comment.
 */
readonly class CommentCreateAction
{
    public function execute(CommentData $data, Task $task, User $user): Comment
    {
        return DB::transaction(function () use ($data, $task, $user) {
            return Comment::create([
                'organization_id' => $task->organization_id,
                'task_id' => $task->id,
                'user_id' => $user->id,
                'content' => $data->content,
            ]);
        });
    }
}
