<?php

use App\Database\Concerns\HasIndexHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use HasIndexHelpers;
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fcm_tokens', function (Blueprint $table) {
            if (!Schema::hasColumn('fcm_tokens', 'device_id')) {
                $table->string('device_id')->nullable()->after('token')->index();
            }
            if (!Schema::hasColumn('fcm_tokens', 'device_type')) {
                $table->string('device_type')->nullable()->after('device_id'); // ios, android, web
            }
            if (!Schema::hasColumn('fcm_tokens', 'app_version')) {
                $table->string('app_version')->nullable()->after('device_type');
            }

            // Add composite index for efficient queries
            if (!$this->indexExists('fcm_tokens', 'fcm_tokens_doctor_device_index')) {
                $table->index(['doctor_id', 'device_id'], 'fcm_tokens_doctor_device_index');
            }

            // Drop the unique constraint on token to allow same token for different users
            // but add composite unique constraint for user + device
            if ($this->indexExists('fcm_tokens', 'fcm_tokens_token_unique')) {
                $table->dropUnique(['token']);
            }
            if (!$this->indexExists('fcm_tokens', 'fcm_tokens_doctor_device_unique')) {
                $table->unique(['doctor_id', 'device_id'], 'fcm_tokens_doctor_device_unique');
            }
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
