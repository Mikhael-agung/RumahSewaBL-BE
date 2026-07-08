<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\RentalController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\PaymentDeadlineController;
use App\Http\Controllers\Api\NotificationController;

Route::get('/health', fn() => response()->json(['status' => 'ok']));

// Public routes
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api')->name('auth.refresh');

// Protected routes
Route::middleware('auth:api')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
    Route::get('/payments/{id}/download', [PaymentController::class, 'download'])->name('payments.download');
    Route::get('/payments/{id}/invoice', [PaymentController::class, 'invoice'])->name('payments.invoice');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Penyewa only
    Route::middleware('role:penyewa')->group(function () {
        Route::post('/payments/upload', [PaymentController::class, 'upload']);
        Route::get('/payments/history', [PaymentController::class, 'history']);
    });

    // Manager & Administrator
    Route::middleware('role:manager,administrator')->group(function () {
        Route::get('/payments/payment-verify', [PaymentController::class, 'paymentVerify']);
        Route::get('/reports/payments', [PaymentController::class, 'report']);
        Route::get('/reports/payments/export', [PaymentController::class, 'exportExcel']);
        Route::post('/payments/manual', [PaymentController::class, 'manual']);
        Route::post('/payments/{id}/status', [PaymentController::class, 'updateStatus']);
        Route::apiResource('buildings', BuildingController::class);
        Route::apiResource('rooms', RoomController::class);
        Route::apiResource('tenants', TenantController::class);
        Route::apiResource('rentals', RentalController::class);

        Route::get('/deadlines', [PaymentDeadlineController::class, 'index'])->name('paymentdeadline.index');
        Route::post('/deadlines', [PaymentDeadlineController::class, 'store'])->name('paymentdeadline.store');
        Route::patch('/deadlines/{month}/{year}', [PaymentDeadlineController::class, 'update'])->name('paymentdeadline.update');
        Route::get('/deadlines/overdue', [PaymentDeadlineController::class, 'overdue'])->name('paymentdeadline.overdue');
    });

    // Administrator only
    Route::middleware('role:administrator')->group(function () {
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activitylog.index');
        Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activitylog.show');
    });
});