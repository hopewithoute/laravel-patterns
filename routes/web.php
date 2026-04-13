<?php

use App\Supports\RouteHelper;
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
    return redirect()->route('dashboard.index');
})->name('home');

// Auto-load all route files from the web directory
RouteHelper::loadRoutesFromDirectory(__DIR__.'/web');
