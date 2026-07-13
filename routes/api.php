<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MobileDisplayController;
use App\Http\Controllers\Api\MobileEventsController;
use App\Http\Controllers\Api\MobileRondesController;
use App\Http\Controllers\Api\MobileUserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::prefix('api-fempinya')->group(function (): void {
        Route::get('/mobile_user_context', [MobileUserController::class, 'context']);
        Route::get('/mobile_user_profile',  [MobileUserController::class, 'profile']);

        Route::get('/mobile_events',         [MobileEventsController::class, 'index']);
        Route::put('/mobile_events/{id}',    [MobileEventsController::class, 'updateAttendance']);

        Route::get('/mobile_rondas',         [MobileRondesController::class, 'index']);
        Route::get('/mobile_rondas/{id}',    [MobileRondesController::class, 'show']);

        Route::get('/public_display_url',    [MobileDisplayController::class, 'url']);
    });
});
