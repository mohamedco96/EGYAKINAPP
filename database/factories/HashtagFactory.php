<?php

namespace Database\Factories;

use App\Models\Hashtag;
use Illuminate\Database\Eloquent\Factories\Factory;

class HashtagFactory extends Factory
{
    protected $model = Hashtag::class;

    public function definition()
    {
        return [
            'tag' => $this->faker->word,
            'usage_count' => $this->faker->numberBetween(1, 100),
        ];
    }
}
