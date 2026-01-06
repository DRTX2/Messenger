<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Message;

class ChatController extends Controller
{
    public function users(){
        $users = User::where('id', '!=', auth('api')->id())->get();
        return response()->json($users);
    }

    public function messages(User $user){
        $userId = auth('api')->id();
        $receiverId = $user->id;
        
        $messages = Message::where(function ($query) use ($userId, $receiverId){
            $query->where('receiver_id', $receiverId)
                ->where('sender_id', $userId);
        })->orWhere(function ($query) use ($userId, $receiverId){
            $query->where('receiver_id', $userId)
                ->where('sender_id', $receiverId);
        })->orderBy('created_at', 'asc')->get();

        return response()->json($messages);
    }

    public function send(Request $request, User $user){
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $message = Message::create([
            'sender_id' => auth('api')->id(),
            'receiver_id' => $user->id,
            'content' => $validated['message'],
        ]);

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message
        ], 201);
    }
}

