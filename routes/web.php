<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Home Route
Route::get('/', function () {
    return inertia('Landing');
})->name('home');

// Placeholder Routes
Route::get('/terms', function () {
    return inertia('Terms');
})->name('terms');
Route::get('/privacy', function () {
    return inertia('Privacy');
})->name('privacy');
Route::get('/api', function () {
    return inertia('Api');
})->name('api');
Route::get('/contact', function () {
    return inertia('Contact');
})->name('contact');

// Domain Routes
require __DIR__.'/web/auth.php';
require __DIR__.'/web/dashboard.php';
require __DIR__.'/web/projects.php';
require __DIR__.'/web/settings.php';
require __DIR__.'/web/tasks.php';
require __DIR__.'/web/team.php';
require __DIR__.'/web/workspace.php';
