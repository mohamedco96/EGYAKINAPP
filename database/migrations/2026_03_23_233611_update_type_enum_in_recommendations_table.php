<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MODIFY ENUM is MySQL-specific; SQLite uses TEXT and does not require this migration
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE `recommendations` MODIFY `type` ENUM('note', 'rec', 'medication', 'procedure', 'lifestyle', 'follow-up', 'dietary', 'other') NOT NULL DEFAULT 'rec'");
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE `recommendations` MODIFY `type` ENUM('note', 'rec') NOT NULL DEFAULT 'rec'");
        }
    }
};
