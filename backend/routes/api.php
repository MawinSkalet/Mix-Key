<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\StationController;
use App\Http\Controllers\TelemetryIngestController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\CapController;

Route::prefix('v1')->group(function () {
    
    // 🔓 1. Guest / Public APIs (throttle to 60 requests/minute)
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/stations', [StationController::class, 'index']);
        Route::get('/stations/{id}', [StationController::class, 'show']);
        Route::get('/stations/{id}/water-level', [StationController::class, 'waterLevelHistory']);
        Route::get('/alerts/active', [AlertController::class, 'activeAlerts']);
    });

    Route::middleware('throttle:120,1')->post('/telemetry/ingest', [TelemetryIngestController::class, 'ingest']);
    Route::post('/cap/alert', [CapController::class, 'inboundAlert']);

    // 🔐 3. Authentication APIs
    Route::post('/auth/login', [AuthController::class, 'login']);

    // 🔒 4. Protected Endpoints (Requires auth:sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // 🔒 Staff & Admin allowed APIs (role: staff or admin)
        Route::middleware('role:staff,admin')->group(function () {
            Route::get('/staff/stations/{id}/telemetry', [StationController::class, 'telemetry']);
            Route::patch('/staff/stations/{id}/thresholds', [StationController::class, 'updateThresholds']);
            
            Route::get('/staff/stations/{id}/maintenance', [MaintenanceController::class, 'index']);
            Route::post('/staff/maintenance', [MaintenanceController::class, 'store']);
            Route::post('/staff/maintenance-requests', [MaintenanceController::class, 'storeRequest']);
            
            Route::get('/staff/notifications', [NotificationController::class, 'index']);
            Route::put('/staff/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        });

        // 🔑 Admin-only allowed APIs (role: admin)
        Route::middleware('role:admin')->group(function () {
            Route::get('/admin/users', [AdminUserController::class, 'index']);
            Route::post('/admin/users', [AdminUserController::class, 'store']);
            Route::patch('/admin/users/{id}/role', [AdminUserController::class, 'updateRole']);
            Route::delete('/admin/users/{id}', [AdminUserController::class, 'destroy']);
        });
    });

});
