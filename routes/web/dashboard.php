<?php

use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'ensure_workspace_selected',
])->group(static function () {

    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('dashboard.index');
});
