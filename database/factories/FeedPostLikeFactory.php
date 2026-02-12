<?php

namespace Database\Factories;

use App\Models\FeedPostLike;
use App\Models\FeedPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FeedPostLike>
 */
class FeedPostLikeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FeedPostLike::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'feed_post_id' => FeedPost::factory(),
            'doctor_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the like belongs to a specific post.
     */
    public function forPost(FeedPost $post): static
    {
        return $this->state(fn (array $attributes) => [
            'feed_post_id' => $post->id,
        ]);
    }

    /**
     * Indicate that the like belongs to a specific doctor.
     */
    public function byDoctor(User $doctor): static
    {
        return $this->state(fn (array $attributes) => [
            'doctor_id' => $doctor->id,
        ]);
    }
}
