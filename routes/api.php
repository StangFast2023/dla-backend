<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CallingDlaController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//---- gets
Route::get('/data-stats', [CallingDlaController::class, 'getStats']);
Route::get('/prediction-user-detail/{regionId}/{areaId}/{positionId}/{sequence}', [CallingDlaController::class, 'predictionUserDetail']);
Route::get('/listed-position-detail/{id}', [CallingDlaController::class, 'getPositionDetailByZone']);

//---- post
Route::post('/updating-tab4-table', [CallingDlaController::class, 'updateTableForTab4']);
