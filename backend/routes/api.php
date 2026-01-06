<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function(){
    Route::get('/chat', [ChatController::class, 'index']);
    Route::get('/chat/{user}', [ChatController::class, 'show']);
    Route::post('/chat/{user}', [ChatController::class, 'store']);
});