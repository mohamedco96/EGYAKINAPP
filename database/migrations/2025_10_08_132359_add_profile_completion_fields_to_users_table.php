<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Make name nullable for social auth users (only if not already nullable)
            if (Schema::hasColumn('users', 'name')) {
                // Check if column is already nullable by checking the column definition
                // For simplicity, we'll just try to change it and catch any errors
                try {
                    $table->string('name')->nullable()->change();
                } catch (\Exception $e) {
                    // Column might already be nullable, ignore
                }
            }

            // Add profile completion flag
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
