<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Section>
 */
class SectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => fake()->numberBetween(1,50),
            'patient_id' => fake()->numberBetween(1,200),
            'section_1' => fake()->boolean(100),
            'section_2' => fake()->boolean(0),
            'section_3' => fake()->boolean(0),
            'section_4' => fake()->boolean(0),
            'section_5' => fake()->boolean(0),
            'section_6' => fake()->boolean(0),
            'section_7' => fake()->boolean(0),
            'submit_status' => fake()->boolean(0),
            'outcome_status' => fake()->boolean(0),
        ];
    }
}
