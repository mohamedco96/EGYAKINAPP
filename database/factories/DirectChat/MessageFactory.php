<?php

namespace Database\Factories\DirectChat;

use App\Models\User;
use App\Modules\DirectChat\Models\Conversation;
use App\Modules\DirectChat\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => User::factory(),
            'type' => 'text',
            'content' => fake()->sentence(),
            'file_metadata' => null,
            'reply_to_id' => null,
        ];
    }

    public function text(string $content = 'Hello!'): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'text',
            'content' => $content,
            'file_metadata' => null,
        ]);
    }
}
