<?php

namespace Database\Seeders;

use App\Modules\Settings\Models\Settings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default settings if none exist
        if (Settings::count() === 0) {
            Settings::create([
                'app_freeze' => false,
                'force_update' => false,
            ]);
        }
    }
}
