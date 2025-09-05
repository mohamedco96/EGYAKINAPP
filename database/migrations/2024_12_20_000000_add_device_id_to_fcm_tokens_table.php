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
        Schema::table('fcm_tokens', function (Blueprint $table) {
            $table->string('device_id')->nullable()->after('token')->index();
            $table->string('device_type')->nullable()->after('device_id'); // ios, android, web
            $table->string('app_version')->nullable()->after('device_type');

            // Add composite index for efficient queries
            $table->index(['doctor_id', 'device_id'], 'fcm_tokens_doctor_device_index');

            // Drop the unique constraint on token to allow same token for different users
            // but add composite unique constraint for user + device
            $table->dropUnique(['token']);
            $table->unique(['doctor_id', 'device_id'], 'fcm_tokens_doctor_device_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fcm_tokens', function (Blueprint $table) {
            $table->dropIndex('fcm_tokens_doctor_device_index');
            $table->dropUnique('fcm_tokens_doctor_device_unique');
            $table->dropColumn(['device_id', 'device_type', 'app_version']);

            // Restore unique constraint on token
            $table->unique('token');
        });
    }
};
