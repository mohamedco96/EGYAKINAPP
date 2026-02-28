<?php

namespace Database\Factories;

use App\Modules\Patients\Models\Patients;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Patients\Models\Patients>
 */
class PatientsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Patients::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'doctor_id' => User::factory(),
            'hidden' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the patient is hidden.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'hidden' => true,
        ]);
    }

    /**
     * Indicate that the patient belongs to a specific doctor.
     */
    public function forDoctor(User $doctor): static
    {
        return $this->state(fn (array $attributes) => [
            'doctor_id' => $doctor->id,
        ]);
    }
}
