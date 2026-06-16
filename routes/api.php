<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DistributionController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\InternalRequestController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', DashboardController::class);
    Route::get('/lookups', LookupController::class);
    Route::get('/reports', [ReportController::class, 'index']);

    Route::apiResource('users', UserController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('documents', DocumentController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('stock-movements', StockMovementController::class)
        ->only(['index', 'store', 'show'])
        ->parameters(['stock-movements' => 'stockMovement']);
    Route::apiResource('internal-requests', InternalRequestController::class)
        ->parameters(['internal-requests' => 'internalRequest']);
    Route::post('/internal-requests/{internalRequest}/approve', [InternalRequestController::class, 'approve']);
    Route::post('/internal-requests/{internalRequest}/reject', [InternalRequestController::class, 'reject']);
    Route::apiResource('distributions', DistributionController::class);
    Route::apiResource('activity-logs', ActivityLogController::class)->only(['index', 'show']);
    Route::apiResource('settings', SettingController::class);
});
