<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehiclesController;
use App\Http\Controllers\Api\RecordsController;
use App\Http\Controllers\Api\CrashesController;
use App\Http\Controllers\Api\DriversController;
use App\Http\Controllers\Api\ForgotPasswordController;



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();


});

// Login & Register 
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });


});



// CRUD Operation for (Vehicles, Records, Crashes, Drivers)

Route::middleware(['auth:sanctum', 'owner'])->group(function () {

    Route::middleware('auth:sanctum')->prefix('vehicles')->group(function () {

        Route::get('show-all', [VehiclesController::class, 'index']);
        Route::post('add', [VehiclesController::class, 'store']);
        Route::get('show/{id}', [VehiclesController::class, 'show']);
        Route::patch('update/{id}', [VehiclesController::class, 'update']);
        Route::delete('delete/{id}', [VehiclesController::class, 'delete']);


        Route::prefix('records')->group(callback: function () {
            Route::get('show-all', [RecordsController::class, 'index']);
            Route::post('add', [RecordsController::class, 'store']);
            Route::delete('delete/{id}', [RecordsController::class, 'delete']);
        });

        Route::prefix('crashes')->group(function () {
            Route::get('show-all', [CrashesController::class, 'index']);
            Route::post('add', [CrashesController::class, 'store']);
            Route::delete('delete/{id}', [CrashesController::class, 'delete']);

        });

        Route::prefix('drivers')->group(function () {
            Route::get('show-all', [DriversController::class, 'index']);
            Route::post('add', [DriversController::class, 'store']);
            Route::get('show/{id}', [DriversController::class, 'show']);
            Route::patch('update/{id}', [DriversController::class, 'update']);
            Route::delete('delete/{id}', [DriversController::class, 'delete']);
        });
    });

});


Route::post('/forget-password', [ForgotPasswordController::class, 'sendOtp']);
Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);

