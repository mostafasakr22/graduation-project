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



// CRUD Operation for (Vehicles, Records, Crashes)

Route::middleware('auth:sanctum')->prefix('vehicles')->group(function () {
    Route::get('show-all', [VehiclesController::class, 'index']);
    Route::post('add', [VehiclesController::class, 'store']);
    Route::get('show/{id}', [VehiclesController::class, 'show']);
    Route::patch('update/{id}', [VehiclesController::class, 'update']);
    Route::delete('delete/{id}', [VehiclesController::class, 'delete']);


    Route::prefix('records')->group(function () {
        Route::get('show-all', [RecordsController::class, 'index']);
        Route::post('add', [RecordsController::class, 'store']);
    });
    
    Route::prefix('crashes')->group(function () {
        Route::get('show-all', [CrashesController::class, 'index']);
        Route::post('add', [CrashesController::class, 'store']);
    });
});

