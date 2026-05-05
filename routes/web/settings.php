<?php

use App\Http\Controllers\SettingsController;

/*
|--------------------------------------------------------------------------
| Settings Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'ensure_workspace_selected',
])->group(static function () {

    Route::get('settings', [SettingsController::class, 'index'])
        ->name('settings.index');

    Route::put('settings/profile', [SettingsController::class, 'updateProfile'])
        ->name('settings.profile.update');

    Route::put('settings/password', [SettingsController::class, 'updatePassword'])
        ->name('settings.password.update');

    Route::post('settings/tokens', [SettingsController::class, 'storeToken'])
        ->name('settings.tokens.store');

    Route::delete('settings/tokens/{id}', [SettingsController::class, 'destroyToken'])
        ->name('settings.tokens.destroy');
});
