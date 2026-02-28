<?php

namespace Database\Factories;

use App\Modules\Consultations\Models\Consultation;
use App\Modules\Patients\Models\Patients;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Consultations\Models\Consultation>
 */
class ConsultationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Consultation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'doctor_id' => User::factory(),
            'patient_id' => Patients::factory(),
            'consult_message' => fake()->paragraph(),
            'status' => 'pending',
            'is_open' => true,
        ];
    }

    /**
     * Indicate that the consultation is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_open' => false,
            'status' => 'closed',
        ]);
    }

    /**
     * Indicate that the consultation is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_open' => true,
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the consultation is answered.
     */
    public function answered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'answered',
        ]);
    }

    /**
     * Indicate that the consultation belongs to a specific doctor and patient.
     */
    public function for(User $doctor, Patients $patient): static
    {
        return $this->state(fn (array $attributes) => [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
        ]);
    }
}
