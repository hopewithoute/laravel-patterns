<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| API versioning via route prefix. Each version can have its own set of
| controllers and routes. When deprecating a version, keep it running
| while clients migrate to the new version.
|
*/

// Default to latest version
Route::redirect('/api', '/api/v1', 302);

Route::prefix('v1')->group(base_path('routes/api/v1.php'));
