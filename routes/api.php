<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehiclesController;
use App\Http\Controllers\Api\RecordsController;
use App\Http\Controllers\Api\CrashesController;



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



// Data
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('vehicles')->group(function () {
        Route::get('/', [VehiclesController::class, 'index']);
        Route::post('/', [VehiclesController::class, 'store']);
        Route::get('/{id}', [VehiclesController::class, 'show']);
        Route::put('/{id}', [VehiclesController::class, 'update']);
        Route::delete('/{id}', [VehiclesController::class, 'destroy']);
    });

    Route::prefix('records')->group(function () {
        Route::get('/', [RecordsController::class, 'index']);
        Route::post('/', [RecordsController::class, 'store']);
    });
    
    Route::prefix('crashes')->group(function () {
        Route::get('/', [CrashesController::class, 'index']);
        Route::post('/', [CrashesController::class, 'store']);
    });
});

