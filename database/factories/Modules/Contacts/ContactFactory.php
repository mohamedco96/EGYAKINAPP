<?php

namespace Database\Factories\Modules\Contacts;

use App\Modules\Contacts\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'doctor_id' => User::factory(),
            'message' => $this->faker->text(200),
        ];
    }
}
