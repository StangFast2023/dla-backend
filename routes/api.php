<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CallingDlaController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/data-stats', [CallingDlaController::class, 'getStats']);
Route::get('/listed-position-detail/{id}', [CallingDlaController::class, 'getPositionDetailByZone']);
