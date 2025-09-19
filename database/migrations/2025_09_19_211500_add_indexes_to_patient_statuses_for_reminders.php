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
        Schema::table('patient_statuses', function (Blueprint $table) {
            // Composite index for reminder queries (key + status + created_at)
            $table->index(['key', 'status', 'created_at'], 'idx_patient_statuses_reminder_lookup');

            // Composite index for patient-doctor-key lookups
            $table->index(['patient_id', 'doctor_id', 'key'], 'idx_patient_statuses_patient_doctor_key');

            // Index for status queries by key
            $table->index(['key', 'status'], 'idx_patient_statuses_key_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_statuses', function (Blueprint $table) {
            $table->dropIndex('idx_patient_statuses_reminder_lookup');
            $table->dropIndex('idx_patient_statuses_patient_doctor_key');
            $table->dropIndex('idx_patient_statuses_key_status');
        });
    }
};
