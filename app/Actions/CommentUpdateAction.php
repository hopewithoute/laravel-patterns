<?php

namespace App\Actions;

use App\Data\CommentData;
use App\Models\Comment;
use Illuminate\Support\Facades\DB;

/**
 * Action to update an existing comment.
 */
class CommentUpdateAction
{
    public function execute(CommentData $data, Comment $comment): Comment
    {
        return DB::transaction(function () use ($data, $comment) {
            $comment->update($data->toModelData());

            return $comment->fresh();
        });
    }
}
