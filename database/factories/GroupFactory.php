<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Group>
 */
class GroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Group::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'header_picture' => null,
            'group_image' => null,
            'privacy' => 'Public',
            'owner_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the group is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'privacy' => 'Private',
        ]);
    }

    /**
     * Indicate that the group is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'privacy' => 'Public',
        ]);
    }

    /**
     * Indicate that the group has images.
     */
    public function withImages(): static
    {
        return $this->state(fn (array $attributes) => [
            'header_picture' => 'groups/headers/' . fake()->uuid() . '.jpg',
            'group_image' => 'groups/images/' . fake()->uuid() . '.jpg',
        ]);
    }

    /**
     * Indicate that the group belongs to a specific owner.
     */
    public function ownedBy(User $owner): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => $owner->id,
        ]);
    }
}
