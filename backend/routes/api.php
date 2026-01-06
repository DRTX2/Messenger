<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function(){
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);

    // Chat routes
    Route::prefix('chat')->group(function () {
        Route::get('/', [ChatController::class, 'users']);
        Route::get('/unread-count', [ChatController::class, 'unreadCount']);
        Route::get('/{user}', [ChatController::class, 'messages']);
        Route::post('/{user}', [ChatController::class, 'send']);
        Route::delete('/{message}', [ChatController::class, 'deleteMessage']);
    });
});