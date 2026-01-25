<?php

use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/users', function (Request $request) {
        return response()->json(User::all());
    });

});





