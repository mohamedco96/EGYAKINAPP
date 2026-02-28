<?php

namespace Database\Factories;

use App\Models\FeedPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedPostFactory extends Factory
{
    protected $model = FeedPost::class;

    public function definition()
    {
        // Define media types
        $mediaTypes = ['image', 'video'];
        // Randomly select a media type
        $mediaType = $this->faker->randomElement($mediaTypes);

        // Define media path based on media type
        $mediaPath = $mediaType === 'image' 
            ? $this->faker->imageUrl() 
            : $this->faker->url();

        return [
            'doctor_id' => User::factory(),
            'content' => $this->faker->sentence,
            'media_type' => $mediaType,
            'media_path' => $mediaPath,
            'visibility' => 'Public',
        ];
    }
}