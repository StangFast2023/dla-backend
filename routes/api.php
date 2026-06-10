<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CallingDlaController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//---- gets
Route::get('/recruitment/tab1', [CallingDlaController::class, 'getDataTab1']);
Route::get('/recruitment/tab2', [CallingDlaController::class, 'getDataTab2']);
Route::get('/recruitment/tab3', [CallingDlaController::class, 'getDataTab3']);
Route::get('/recruitment/tab4', [CallingDlaController::class, 'getDataTab4']);
Route::get('/recruitment/tab5', [CallingDlaController::class, 'getDataTab5']);

Route::get('/prediction-user-detail/{regionId}/{areaId}/{positionId}/{sequence}/{frequency}', [CallingDlaController::class, 'predictionUserDetail']);
Route::get('/listed-position-detail/{id}', [CallingDlaController::class, 'getPositionDetailByZone']);

//---- post
Route::post('/updating-tab4-table', [CallingDlaController::class, 'updateTableForTab4']);
