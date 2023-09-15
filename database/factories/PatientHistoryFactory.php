<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PatientHistory>
 */
class PatientHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fake()->numberBetween(1,50),
            'name' => fake()->name(),
            'hospital' => fake()->words(2, true),
            'collected_data_from' => fake()->sentence(),
            'NID' => fake()->biasedNumberBetween(12),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'age' => fake()->randomNumber(2),
            'gender' => fake()->words(1, true),
            'occupation' => fake()->words(1, true),
            'residency' => fake()->words(1, true),
            'governorate' => fake()->words(1, true),
            'marital_status' => fake()->words(1, true),
            'educational_level' => fake()->words(1, true),
            'special_habits_of_the_patient' => fake()->words(1, true),
            'DM' => fake()->words(1, true),
            'DM_duration' => fake()->randomNumber(2),
            'HTN' => fake()->words(1, true),
            'HTN_duration' => fake()->randomNumber(2)
        ];
    }
}
