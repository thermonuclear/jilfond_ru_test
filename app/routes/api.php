<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::apiResource('notifications', NotificationController::class)
    ->only(['store', 'show', 'index']);

Route::prefix('reports')->group(function () {
    Route::post('/', [ReportController::class, 'store']);
    Route::get('/{report}', [ReportController::class, 'show']);
    Route::get('/{report}/download', [ReportController::class, 'download']);
});
