<?php

use App\Http\Controllers\ProjectController;

/*
|--------------------------------------------------------------------------
| Project Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'ensure_workspace_selected',
])->group(static function () {

    Route::resource('projects', ProjectController::class);

    // Additional project routes
    Route::get('projects/{project}/tasks', [ProjectController::class, 'tasks'])
        ->name('projects.tasks');
});
