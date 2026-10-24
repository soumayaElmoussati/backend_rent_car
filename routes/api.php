<?php

use Illuminate\Support\Facades\Route;

// Importation de tous les contrôleurs
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\WashController;
use App\Http\Controllers\AccidentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Ici sont enregistrées les routes de l'API pour l'application de gestion
| de location de voitures. Toutes les routes protégées nécessitent un
| token JWT valide via le middleware 'auth:api'.
|
*/


Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']); 
    Route::post('login', [AuthController::class, 'login']);   
});


Route::middleware('auth:api')->group(function () {


    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);      
        Route::post('logout', [AuthController::class, 'logout']);  
        Route::post('refresh', [AuthController::class, 'refresh']); 
    });


    Route::prefix('clients')->group(function () {
        Route::get('/', [ClientController::class, 'index']);  
        Route::post('/', [ClientController::class, 'store']);   
        Route::get('/{client}', [ClientController::class, 'show']);  
        Route::put('/{client}', [ClientController::class, 'update']); 
        Route::delete('/{client}', [ClientController::class, 'destroy']); 
        Route::patch('/{client}/blacklist', [ClientController::class, 'toggleBlacklist']);
    });


    Route::prefix('vehicles')->group(function () {
        Route::get('/', [VehicleController::class, 'index']);         
        Route::post('/', [VehicleController::class, 'store']);        
        Route::get('/{vehicle}', [VehicleController::class, 'show']); 
        Route::put('/{vehicle}', [VehicleController::class, 'update']); 
        Route::delete('/{vehicle}', [VehicleController::class, 'destroy']); 
        Route::patch('/{vehicle}/status', [VehicleController::class, 'updateStatus']); 
    });


    Route::prefix('reservations')->group(function () {
        Route::get('/', [ReservationController::class, 'index']);      
        Route::post('/calculate-price', [ReservationController::class, 'calculatePrice']); 
        Route::post('/', [ReservationController::class, 'store']);  
        Route::get('/{reservation}', [ReservationController::class, 'show']); 
        Route::put('/{reservation}', [ReservationController::class, 'update']); 
        Route::delete('/{reservation}', [ReservationController::class, 'destroy']); 
        Route::patch('/{reservation}/cancel', [ReservationController::class, 'cancel']); 
        Route::patch('/{reservation}/confirm', [ReservationController::class, 'confirm']);
    });


    Route::prefix('maintenances')->group(function () {
        Route::get('/', [MaintenanceController::class, 'index']);       
        Route::post('/', [MaintenanceController::class, 'store']);     
        Route::get('/{maintenance}', [MaintenanceController::class, 'show']); 
        Route::put('/{maintenance}', [MaintenanceController::class, 'update']); 
        Route::delete('/{maintenance}', [MaintenanceController::class, 'destroy']); 
        Route::patch('/{maintenance}/complete', [MaintenanceController::class, 'complete']);
        Route::patch('/{maintenance}/cancel', [MaintenanceController::class, 'cancel']); 
    });


    Route::prefix('washes')->group(function () {
        Route::get('/', [WashController::class, 'index']);     
        Route::post('/', [WashController::class, 'store']);      
        Route::get('/{wash}', [WashController::class, 'show']); 
        Route::put('/{wash}', [WashController::class, 'update']); 
        Route::delete('/{wash}', [WashController::class, 'destroy']); 
    });


    Route::prefix('accidents')->group(function () {
        Route::get('/', [AccidentController::class, 'index']);     
        Route::post('/', [AccidentController::class, 'store']);   
        Route::get('/{accident}', [AccidentController::class, 'show']); 
        Route::put('/{accident}', [AccidentController::class, 'update']);
        Route::delete('/{accident}', [AccidentController::class, 'destroy']); 
        Route::patch('/{accident}/status', [AccidentController::class, 'updateStatus']); 
    });

});