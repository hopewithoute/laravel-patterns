<?php

namespace App\Http\Controllers;

use App\Actions\CommentCreateAction;
use App\Actions\CommentDeleteAction;
use App\Data\CommentData;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Store a new comment on a task.
     */
    public function store(CommentData $data, Task $task, CommentCreateAction $action, Request $request): RedirectResponse
    {
        $action->execute($data, $task, $request->user());

        return redirect()->back()
            ->with('success', 'Comment added.');
    }

    /**
     * Delete a comment.
     */
    public function destroy(Task $task, Comment $comment, CommentDeleteAction $action, Request $request): RedirectResponse
    {
        $action->execute($comment, $request->user());

        return redirect()->back()
            ->with('success', 'Comment deleted.');
    }
}
