<?php

use App\Models\User;
use App\Models\Message;

test('authenticated user can send a message', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $response = $this->actingAs($sender, 'api')
        ->postJson("/api/chat/{$receiver->id}", [
            'message' => 'Hello World'
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Message sent successfully'
        ]);

    $this->assertDatabaseHas('messages', [
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'content' => 'Hello World'
    ]);
});

test('user cannot chat with themselves', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'api')
        ->postJson("/api/chat/{$user->id}", [
            'message' => 'Self talk'
        ]);

    $response->assertStatus(400)
             ->assertJson([
                 'success' => false,
                 'message' => 'Cannot send messages to yourself'
             ]);
});

test('can retrieve conversation', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    Message::factory()->create([
        'sender_id' => $user1->id,
        'receiver_id' => $user2->id,
        'content' => 'Hi there'
    ]);

    $response = $this->actingAs($user1, 'api')
        ->getJson("/api/chat/{$user2->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'content', 'sender', 'receiver']
            ],
            'success'
        ]);
});
