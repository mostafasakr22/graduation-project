<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UsersController; 
use App\Http\Controllers\Api\VehiclesController;
use App\Http\Controllers\Api\CrashesController;
use App\Http\Controllers\Api\DriversController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\TripsController;


// ========================================================================
// 1. Hardware Communication (رابط الهاردوير) 📟
// ========================================================================

Route::post('/hardware/log-location', [TripsController::class, 'logLocation']); // التتبع الذكي
Route::post('/hardware/crash-report', [CrashesController::class, 'store']);     // الإبلاغ عن الحوادث
Route::post('/hardware/end-trip', [TripsController::class, 'hardwareEndTrip']); // انهاء الرحله


// ========================================================================
// 2. Mobile App (Owner Only) - تطبيق المالك 📱
// ========================================================================

// Authentication
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    });
    
    // Password Reset
    Route::post('/forget-password', [ForgotPasswordController::class, 'sendOtp']);
    Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);

    
// Protected Routes (للمالك فقط)
Route::middleware(['auth:sanctum', 'owner'])->group(function () {
    
    // User Info & Logout
    Route::get('/user', function (Request $request) { return $request->user(); });
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']); 


    // 🔔 Notifications (أهم حاجة للمالك)
    Route::get('/notifications', [UsersController::class, 'getNotifications']);
    Route::post('/notifications/read', [UsersController::class, 'markRead']);

    // 🗺️ Trips History (عرض الرحلات السابقة والحالية)
    // (المالك بس هو اللي بيشوف، مش بيعمل Start)
    Route::get('/trips/history', [TripsController::class, 'getHistory']);
    Route::get('/trips/{id}', [TripsController::class, 'show']);

    // 🚗 Vehicles Management
    Route::prefix('vehicles')->group(function () {
        Route::get('show-all', [VehiclesController::class, 'index']);
        Route::post('add', [VehiclesController::class, 'store']);
        Route::get('show/{id}', [VehiclesController::class, 'show']);
        Route::patch('update/{id}', [VehiclesController::class, 'update']);
        Route::delete('delete/{id}', [VehiclesController::class, 'delete']);
    });

    // 👤 Owners Profile
    Route::prefix('owners')->group(function () {
        Route::get('show-all', [UsersController::class, 'index']); 
        Route::get('show/{id}', [UsersController::class, 'show']);
        Route::patch('update/{id}', [UsersController::class, 'update']);
        Route::delete('delete/{id}', [UsersController::class, 'delete']);
    });

    // 🧑‍✈️ Drivers Management (لسه موجود عشان المالك يسجل مين السواق)
    Route::prefix('drivers')->group(function () {
        Route::get('show-all', [DriversController::class, 'index']);
        Route::post('add', [DriversController::class, 'store']);
        Route::get('show/{id}', [DriversController::class, 'show']);
        Route::patch('update/{id}', [DriversController::class, 'update']);
        Route::delete('delete/{id}', [DriversController::class, 'delete']);
    });

    // 💥 Crashes View (عرض الحوادث فقط)
    Route::prefix('crashes')->group(function () {
        Route::get('show-all', [CrashesController::class, 'index']);
        Route::delete('delete/{id}', [CrashesController::class, 'delete']);
    });

    // تقرير السائق
    Route::get('/drivers/{id}/score', [DriversController::class, 'getScore']);

});