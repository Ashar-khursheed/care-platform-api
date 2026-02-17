<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class MessageSystemTest extends TestCase
{
    // use RefreshDatabase; // Commmented out to avoid clearing dev database if not using separate test DB

    public function test_can_send_message()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        Sanctum::actingAs($sender, ['*']);

        $response = $this->postJson('/api/v1/messages/send', [
            'receiver_id' => $receiver->id,
            'message' => 'Hello, world!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'message', 'conversation_id']]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'message' => 'Hello, world!',
        ]);
    }

    public function test_can_get_conversations()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $conversation = Conversation::create([
            'user1_id' => min($user1->id, $user2->id),
            'user2_id' => max($user1->id, $user2->id),
            'last_message' => 'Test',
            'last_message_at' => now(),
            'last_message_user_id' => $user2->id
        ]);

        Sanctum::actingAs($user1, ['*']);

        $response = $this->getJson('/api/v1/messages/conversations');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_can_get_messages()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $conversation = Conversation::create([
            'user1_id' => min($user1->id, $user2->id),
            'user2_id' => max($user1->id, $user2->id),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user2->id,
            'receiver_id' => $user1->id,
            'message' => 'Hello there',
            'status' => 'sent'
        ]);

        Sanctum::actingAs($user1, ['*']);

        $response = $this->getJson("/api/v1/messages/conversations/{$conversation->id}/messages");

        $response->assertStatus(200)
             ->assertJsonPath('data.0.message', 'Hello there');
    }
}
