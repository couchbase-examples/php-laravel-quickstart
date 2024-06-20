<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AirlineController;
use App\Http\Controllers\AirportController;
use App\Http\Controllers\RouteController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api/v1')->group(function () {
    // Airlines routes
    Route::get('airlines/list', [AirlineController::class, 'index']);
    Route::get('airlines/{id}', [AirlineController::class, 'show']);
    Route::post('airlines/{id}', [AirlineController::class, 'store']);
    Route::put('airlines/{id}', [AirlineController::class, 'update']);
    Route::delete('airlines/{id}', [AirlineController::class, 'destroy']);
    Route::get('airlines/to-airport', [AirlineController::class, 'toAirport']);

    // Airports routes
    Route::get('airports/{id}', [AirportController::class, 'show']);
    Route::post('airports/{id}', [AirportController::class, 'store']);
    Route::put('airports/{id}', [AirportController::class, 'update']);
    Route::delete('airports/{id}', [AirportController::class, 'destroy']);
    Route::get('airports/direct-connections', [AirportController::class, 'directConnections']);

    // Routes routes
    Route::get('routes/{id}', [RouteController::class, 'show']);
    Route::post('routes/{id}', [RouteController::class, 'store']);
    Route::put('routes/{id}', [RouteController::class, 'update']);
    Route::delete('routes/{id}', [RouteController::class, 'destroy']);
});
