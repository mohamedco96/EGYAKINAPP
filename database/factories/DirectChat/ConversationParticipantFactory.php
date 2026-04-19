<?php

namespace Database\Factories\DirectChat;

use App\Models\User;
use App\Modules\DirectChat\Models\Conversation;
use App\Modules\DirectChat\Models\ConversationParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversationParticipant>
 */
class ConversationParticipantFactory extends Factory
{
    protected $model = ConversationParticipant::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'user_id' => User::factory(),
            'role' => 'member',
            'joined_at' => now(),
            'last_read_at' => null,
            'mute_notifications' => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => 'admin']);
    }

    public function muted(): static
    {
        return $this->state(fn (array $attributes) => ['mute_notifications' => true]);
    }
}
