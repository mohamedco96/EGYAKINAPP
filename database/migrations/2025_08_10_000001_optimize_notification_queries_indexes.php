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
        // Composite index to speed eager load of answers filtered by patient and question
        Schema::table('answers', function (Blueprint $table) {
            if (! $this->indexExists('answers', 'answers_patient_question_idx')) {
                $table->index(['patient_id', 'question_id'], 'answers_patient_question_idx');
            }
        });

        // Composite index to speed eager load of patient statuses by patient and key
        Schema::table('patient_statuses', function (Blueprint $table) {
            if (! $this->indexExists('patient_statuses', 'patient_statuses_patient_key_idx')) {
                $table->index(['patient_id', 'key'], 'patient_statuses_patient_key_idx');
            }
        });

        // Composite indexes to speed notification listing and marking read
        Schema::table('notifications', function (Blueprint $table) {
            if (! $this->indexExists('notifications', 'notifications_doctor_created_idx')) {
                $table->index(['doctor_id', 'created_at'], 'notifications_doctor_created_idx');
            }
            if (! $this->indexExists('notifications', 'notifications_doctor_read_idx')) {
                $table->index(['doctor_id', 'read'], 'notifications_doctor_read_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            if ($this->indexExists('answers', 'answers_patient_question_idx')) {
                $table->dropIndex('answers_patient_question_idx');
            }
        });

        Schema::table('patient_statuses', function (Blueprint $table) {
            if ($this->indexExists('patient_statuses', 'patient_statuses_patient_key_idx')) {
                $table->dropIndex('patient_statuses_patient_key_idx');
            }
        });

        Schema::table('notifications', function (Blueprint $table) {
            if ($this->indexExists('notifications', 'notifications_doctor_created_idx')) {
                $table->dropIndex('notifications_doctor_created_idx');
            }
            if ($this->indexExists('notifications', 'notifications_doctor_read_idx')) {
                $table->dropIndex('notifications_doctor_read_idx');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        // Laravel doesn't expose a direct cross-DB way; use Doctrine schema manager if available
        try {
            $connection = Schema::getConnection();
            $schemaManager = $connection->getDoctrineSchemaManager();
            $indexes = $schemaManager->listTableIndexes($table);

            return array_key_exists($indexName, $indexes);
        } catch (\Throwable $e) {
            // Fallback: assume missing to avoid hard failure; duplicate index create is harmless in most DBs
            return false;
        }
    }
};
