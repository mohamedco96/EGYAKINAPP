<?php

namespace Database\Factories\DirectChat;

use App\Models\User;
use App\Modules\DirectChat\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['private', 'social_group', 'case_group']),
            'name' => null,
            'description' => null,
            'image' => null,
            'created_by' => User::factory(),
        ];
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'private', 'name' => null]);
    }

    public function socialGroup(string $name = 'Test Social Group'): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'social_group', 'name' => $name]);
    }

    public function caseGroup(string $name = 'Test Case Group'): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'case_group', 'name' => $name]);
    }
}
