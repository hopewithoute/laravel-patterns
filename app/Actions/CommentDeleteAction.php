<?php

namespace App\Actions;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Action to delete a comment.
 */
readonly class CommentDeleteAction
{
    public function execute(Comment $comment, User $user): void
    {
        DB::transaction(function () use ($comment, $user) {
            // Authorization check
            if ($comment->user_id !== $user->id) {
                abort(403, 'You can only delete your own comments.');
            }

            $comment->delete();
        });
    }
}
