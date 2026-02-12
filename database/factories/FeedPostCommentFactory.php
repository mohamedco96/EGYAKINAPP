<?php

namespace Database\Factories;

use App\Models\FeedPostComment;
use App\Models\FeedPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FeedPostComment>
 */
class FeedPostCommentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FeedPostComment::class;

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
            'comment' => fake()->sentence(),
            'parent_id' => null,
        ];
    }

    /**
     * Indicate that the comment is a reply to another comment.
     */
    public function reply(FeedPostComment $parentComment): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentComment->id,
            'feed_post_id' => $parentComment->feed_post_id,
        ]);
    }

    /**
     * Indicate that the comment belongs to a specific post.
     */
    public function forPost(FeedPost $post): static
    {
        return $this->state(fn (array $attributes) => [
            'feed_post_id' => $post->id,
        ]);
    }

    /**
     * Indicate that the comment belongs to a specific doctor.
     */
    public function byDoctor(User $doctor): static
    {
        return $this->state(fn (array $attributes) => [
            'doctor_id' => $doctor->id,
        ]);
    }
}
