<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// login + logut
// jwt
// refactorizar(agregar servicios)
// crear front con angular, agregar auth, luego mensages.

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function(){
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/chat', [ChatController::class, 'users']);
    Route::get('/chat/{user}', [ChatController::class, 'messages']);
    Route::post('/chat/{user}', [ChatController::class, 'send']);
});