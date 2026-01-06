<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(){
        $users = User::where('id','!=', auth()->id())->get();
        return response()->json($users);
    }

    public function show(User $user){//sino el id del usuario
        $userId=auth()->id();
        $receiberId=$user->id;
        $messages= Message::where(function ($query) use ($userId, $receiberId){
            $query->where('receiver_id', $receiberId)
            ->where('sender_id', $userId);
        })->orWhere(function ($query) use ($userId, $receiberId){
            $query->where('receiver_id', $userId)
            ->where('sender_id', $receiberId);
        })->get();

        return response()->json($messages);
    }

    public function store(Request $request, User $user){
        Message::create ([
            'sender_id'=>auth()->id(),
            'receiver_id'=>$user->id,
            'content'=>$request->content,
        ]);
        return response()->json(['message'=>'Message sent successfully'], 201);
    }
}
