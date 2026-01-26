<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MarketController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    
    Route::apiResource('markets', MarketController::class);
    
    Route::get('/users', function (Request $request) {
        return response()->json(User::all());
    });

});





