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
            if (!$this->indexExists('patient_statuses', 'idx_patient_statuses_reminder_lookup')) {
                $table->index(['key', 'status', 'created_at'], 'idx_patient_statuses_reminder_lookup');
            }

            // Composite index for patient-doctor-key lookups
            if (!$this->indexExists('patient_statuses', 'idx_patient_statuses_patient_doctor_key')) {
                $table->index(['patient_id', 'doctor_id', 'key'], 'idx_patient_statuses_patient_doctor_key');
            }

            // Index for status queries by key
            if (!$this->indexExists('patient_statuses', 'idx_patient_statuses_key_status')) {
                $table->index(['key', 'status'], 'idx_patient_statuses_key_status');
            }
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

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $connection = Schema::getConnection();
            $schemaManager = $connection->getDoctrineSchemaManager();
            $indexes = $schemaManager->listTableIndexes($table);

            return array_key_exists($indexName, $indexes);
        } catch (\Throwable $e) {
            return false;
        }
    }
};
