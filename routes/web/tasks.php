<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\TaskController;

/*
|--------------------------------------------------------------------------
| Task Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'ensure_workspace_selected',
])->group(static function () {

    Route::get('tasks/kanban', [TaskController::class, 'kanban'])
        ->name('tasks.kanban');

    Route::patch('tasks/{task}/move', [TaskController::class, 'move'])
        ->name('tasks.move');

    Route::resource('tasks', TaskController::class);

    // Additional task routes
    Route::put('tasks/{task}/complete', [TaskController::class, 'complete'])
        ->name('tasks.complete');

    Route::put('tasks/{task}/assign', [TaskController::class, 'assign'])
        ->name('tasks.assign');

    // Comment routes
    Route::post('tasks/{task}/comments', [CommentController::class, 'store'])
        ->name('tasks.comments.store');

    Route::delete('tasks/{task}/comments/{comment}', [CommentController::class, 'destroy'])
        ->name('tasks.comments.destroy');
});
