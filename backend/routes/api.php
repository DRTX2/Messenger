<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;

Broadcast::routes(['middleware' => ['auth:api']]);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function(){
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);

    // Conversation routes (Inbox)
    Route::get('/conversations', [App\Http\Controllers\ConversationController::class, 'index']);
    
    // Group routes
    Route::prefix('groups')->group(function () {
        Route::post('/', [App\Http\Controllers\GroupController::class, 'store']);
        Route::put('/{conversation}', [App\Http\Controllers\GroupController::class, 'update']);
        Route::delete('/{conversation}/leave', [App\Http\Controllers\GroupController::class, 'leave']);
        Route::post('/{conversation}/participants', [App\Http\Controllers\GroupController::class, 'addParticipants']);
        Route::delete('/{conversation}/participants/{userId}', [App\Http\Controllers\GroupController::class, 'removeParticipant']);
    });
    
    // Attachment routes
    Route::post('/attachments', [App\Http\Controllers\AttachmentController::class, 'store']);

    // Typing indicator
    Route::post('/conversations/{conversation}/typing', [App\Http\Controllers\TypingController::class, 'typing']);

    // Chat routes
    Route::prefix('chat')->group(function () {
        Route::get('/', [ChatController::class, 'users']);
        Route::get('/unread-count', [ChatController::class, 'unreadCount']);
        
        // Specific message actions
        Route::post('/messages/{message}/favorite', [ChatController::class, 'toggleFavorite']);
        Route::delete('/messages/{message}', [ChatController::class, 'deleteMessage']);
        
        // User-specific actions
        Route::post('/{user}/clear', [ChatController::class, 'clear']);
        Route::get('/{user}', [ChatController::class, 'messages']);
        Route::post('/{user}', [ChatController::class, 'send']);
    });
});