<?php

use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Workspace Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('workspace/select', [WorkspaceController::class, 'select'])
        ->name('workspace.select');

    Route::post('workspace/set', [WorkspaceController::class, 'set'])
        ->name('workspace.set');

    Route::get('workspace/create', [WorkspaceController::class, 'create'])
        ->name('workspace.create');

    Route::post('workspace', [WorkspaceController::class, 'store'])
        ->name('workspace.store');
});
