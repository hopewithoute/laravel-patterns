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
class CommentCreateAction
{
    public function execute(CommentData $data, Task $task, User $user): Comment
    {
        return DB::transaction(function () use ($data, $user) {
            return Comment::create(array_merge(
                $data->toModelData(),
                ['user_id' => $user->id]
            ));
        });
    }
}
