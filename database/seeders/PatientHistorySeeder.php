<?php

namespace Database\Seeders;

use App\Models\PatientHistory;
use Illuminate\Database\Seeder;

class PatientHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PatientHistory::factory()
            ->count(200)
            ->create();
    }
}
