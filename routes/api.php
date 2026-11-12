<?php

use App\Http\Controllers\Api\V1\ExternalPostController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware(['throttle:60,1', 'api.token'])->group(function () {
        Route::apiResource('tasks', TaskController::class);
        Route::get('/external/posts', ExternalPostController::class);
    });

    Route::any('{any}', fn () => response()->json([
        'message' => 'Endpoint tidak ditemukan.',
    ], 404))->where('any', '.*');
});
