<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AiChatMessageStreamController;
use App\Http\Controllers\AiChatSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'ensure_workspace_selected',
])->group(static function () {
    Route::get('ai', [AiChatController::class, 'index'])
        ->name('ai.index');

    Route::post('ai/sessions', [AiChatSessionController::class, 'store'])
        ->name('ai.sessions.store');

    Route::post('ai/sessions/{aiChatSession}/messages/stream', AiChatMessageStreamController::class)
        ->name('ai.sessions.messages.stream')
        ->middleware(['throttle:60,1']);
});
