<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TokenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    // Token management
    Route::get('auth/tokens', [TokenController::class, 'index']);
    Route::post('auth/tokens', [TokenController::class, 'store']);
    Route::delete('auth/tokens/{token}', [TokenController::class, 'destroy'])->withoutScopedBindings();

    // Tasks
    Route::get('tasks', [TaskController::class, 'index']);
    Route::post('tasks', [TaskController::class, 'store']);
    Route::get('tasks/{task}', [TaskController::class, 'show']);
    Route::put('tasks/{task}', [TaskController::class, 'update']);
    Route::delete('tasks/{task}', [TaskController::class, 'destroy']);

    // Projects
    Route::get('projects', [ProjectController::class, 'index']);
    Route::post('projects', [ProjectController::class, 'store']);
    Route::get('projects/{project}', [ProjectController::class, 'show']);
    Route::put('projects/{project}', [ProjectController::class, 'update']);
    Route::delete('projects/{project}', [ProjectController::class, 'destroy']);

    // Comments
    Route::get('tasks/{task}/comments', [CommentController::class, 'index']);
    Route::post('tasks/{task}/comments', [CommentController::class, 'store']);
    Route::get('comments/{comment}', [CommentController::class, 'show']);
    Route::put('comments/{comment}', [CommentController::class, 'update']);
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);

    // Organizations
    Route::get('organizations', [OrganizationController::class, 'index']);
    Route::post('organizations', [OrganizationController::class, 'store']);
    Route::get('organizations/{organization}', [OrganizationController::class, 'show']);
    Route::put('organizations/{organization}', [OrganizationController::class, 'update']);
    Route::get('organizations/{organization}/members', [OrganizationController::class, 'members']);
});
