<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MobileGdprController;
use App\Http\Controllers\Api\MobileDisplayController;
use App\Http\Controllers\Api\MobileEventsController;
use App\Http\Controllers\Api\MobileHomeController;
use App\Http\Controllers\Api\MobileNoticiesController;
use App\Http\Controllers\Api\MobileRondesController;
use App\Http\Controllers\Api\MobileUserController;
use Illuminate\Support\Facades\Route;

// Paths exactes que usa l'app FP3 Flutter
Route::post('/api/auth/login',  [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/api/auth/logout', [AuthController::class, 'logout']);

    Route::prefix('api-fempinya')->group(function (): void {
        Route::get('/mobile_home',           [MobileHomeController::class, '__invoke']);
        Route::get('/mobile_noticies',       [MobileNoticiesController::class, '__invoke']);
        Route::get('/mobile_user_context',   [MobileUserController::class, 'context']);
        Route::get('/mobile_user_profile',  [MobileUserController::class, 'profile']);
        Route::put('/mobile_user_profile',  [MobileUserController::class, 'updateProfile']);

        Route::get('/mobile_events',              [MobileEventsController::class, 'index']);
        Route::get('/mobile_events/{id}',         [MobileEventsController::class, 'show']);
        Route::put('/mobile_events/{id}',         [MobileEventsController::class, 'updateAttendance']);
        Route::post('/mobile_events/{id}/answers', [MobileEventsController::class, 'saveAnswers']);

        Route::get('/mobile_rondas',           [MobileRondesController::class, 'index']);
        Route::get('/mobile_rondas/{id}',    [MobileRondesController::class, 'show']);
        Route::get('/mobile_historial',      [MobileRondesController::class, 'historial']);

        Route::get('/public_display_url',    [MobileDisplayController::class, 'url']);

        Route::get('/mobile_gdpr',           [MobileGdprController::class, 'status']);
        Route::post('/mobile_gdpr/accept',   [MobileGdprController::class, 'accept']);
    });
});
