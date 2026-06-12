<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\RentalController;
use App\Http\Controllers\Api\ActivityLogController;

Route::get('/health', fn() => response()->json(['status' => 'ok']));

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:api')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Penyewa only
    Route::middleware('role:penyewa')->group(function () {
        Route::post('/payments/upload', [PaymentController::class, 'upload']);
        Route::get('/payments/history', [PaymentController::class, 'history']);
    });

    // Manager & Administrator
    Route::middleware('role:manager,administrator')->group(function () {
        Route::get('/payments/pending', [PaymentController::class, 'pending']);
        Route::post('/payments/{id}/verify', [PaymentController::class, 'verify']);
        Route::post('/payments/{id}/reject', [PaymentController::class, 'reject']);
        Route::apiResource('buildings', BuildingController::class);
        Route::apiResource('rooms', RoomController::class);
        Route::apiResource('tenants', TenantController::class);
        Route::apiResource('rentals', RentalController::class);
    });

    // Administrator only
    Route::middleware('role:administrator')->group(function () {
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);
        Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show']);
    });
});