<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehiclesController;
use App\Http\Controllers\Api\RecordsController;
use App\Http\Controllers\Api\CrashesController;
use App\Http\Controllers\Api\DriversController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\TripsController;



    // Register & Login
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

    // Logout
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Forget Password
    Route::post('/forget-password', [ForgotPasswordController::class, 'sendOtp']);
    Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);



    // Trips (owner,driver)
    Route::prefix('trips')->group(function () {
        Route::post('/start', [TripsController::class, 'startTrip']);
        Route::post('/end/{id}', [TripsController::class, 'endTrip']);
        Route::post('/log-location', [TripsController::class, 'logLocation']);
        Route::get('/history', [TripsController::class, 'getHistory']);
        Route::get('/{id}', [TripsController::class, 'show']);
    });

    // Add crash (owner,driver)
    Route::post('/crashes/add', [CrashesController::class, 'store']);


    
    Route::middleware('owner')->group(function () {

        // Vehicles
        Route::prefix('vehicles')->group(function () {
            Route::get('show-all', [VehiclesController::class, 'index']);
            Route::post('add', [VehiclesController::class, 'store']);
            Route::get('show/{id}', [VehiclesController::class, 'show']);
            Route::patch('update/{id}', [VehiclesController::class, 'update']);
            Route::delete('delete/{id}', [VehiclesController::class, 'delete']);
        });

        // Drivers
        Route::prefix('drivers')->group(function () {
            Route::get('show-all', [DriversController::class, 'index']);
            Route::post('add', [DriversController::class, 'store']);
            Route::get('show/{id}', [DriversController::class, 'show']);
            Route::patch('update/{id}', [DriversController::class, 'update']);
            Route::delete('delete/{id}', [DriversController::class, 'delete']);
        });

        // Records
        Route::prefix('records')->group(function () {
            Route::get('show-all', [RecordsController::class, 'index']);
            Route::post('add', [RecordsController::class, 'store']);
            Route::delete('delete/{id}', [RecordsController::class, 'delete']);
        });

        // crashes
        Route::prefix('crashes')->group(function () {
            Route::get('show-all', [CrashesController::class, 'index']);
            Route::delete('delete/{id}', [CrashesController::class, 'delete']);
        });

    }); 

});