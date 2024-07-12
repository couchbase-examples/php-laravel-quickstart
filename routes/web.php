<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AirlineController;
use App\Http\Controllers\AirportController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\HotelController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api/v1')->group(function () {
    // Airlines routes
    Route::get('airlines/list', [AirlineController::class, 'index']);
    Route::get('airlines/to-airport/{destinationAirportCode}', [AirlineController::class, 'toAirport']);
    Route::get('airlines/{id}', [AirlineController::class, 'show']);
    Route::post('airlines/{id}', [AirlineController::class, 'store']);
    Route::put('airlines/{id}', [AirlineController::class, 'update']);
    Route::delete('airlines/{id}', [AirlineController::class, 'destroy']);

    // Airports routes
    Route::get('airports/list', [AirportController::class, 'index']);
    Route::get('airports/direct-connections', [AirportController::class, 'getDirectConnections']);
    Route::get('airports/{id}', [AirportController::class, 'show']);
    Route::post('airports/{id}', [AirportController::class, 'store']);
    Route::put('airports/{id}', [AirportController::class, 'update']);
    Route::delete('airports/{id}', [AirportController::class, 'destroy']);

    // Routes routes
    Route::get('routes/list', [RouteController::class, 'index']);
    Route::get('routes/{id}', [RouteController::class, 'show']);
    Route::post('routes/{id}', [RouteController::class, 'store']);
    Route::put('routes/{id}', [RouteController::class, 'update']);
    Route::delete('routes/{id}', [RouteController::class, 'destroy']);

    // Hotels routes
    Route::get('hotels/autocomplete', [HotelController::class, 'search']);
    Route::get('hotels/filter', [HotelController::class, 'filter']);
});
