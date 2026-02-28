<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $nameIsNullable = false;
        if (Schema::hasColumn('users', 'name')) {
            $nameCol = DB::selectOne(
                "SELECT IS_NULLABLE FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'name'"
            );
            $nameIsNullable = $nameCol && $nameCol->IS_NULLABLE === 'YES';
        }

        Schema::table('users', function (Blueprint $table) use ($nameIsNullable) {
            if (!$nameIsNullable) {
                $table->string('name')->nullable()->change();
            }

            if (!Schema::hasColumn('users', 'profile_completed')) {
                $table->boolean('profile_completed')->default(false)->after('social_verified_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->dropColumn('profile_completed');
        });
    }
};
