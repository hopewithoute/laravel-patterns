<?php

use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Team Management Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'ensure_workspace_selected',
])->group(function () {
    Route::get('team', [UserManagementController::class, 'index'])
        ->name('team.index');

    Route::post('team/invite', [UserManagementController::class, 'invite'])
        ->name('team.invite');

    Route::delete('team/{user}', [UserManagementController::class, 'remove'])
        ->name('team.remove');

    Route::get('team/invite-code', [UserManagementController::class, 'inviteCode'])
        ->name('team.invite-code');

    Route::post('team/regenerate-code', [UserManagementController::class, 'regenerateCode'])
        ->name('team.regenerate-code');
});
