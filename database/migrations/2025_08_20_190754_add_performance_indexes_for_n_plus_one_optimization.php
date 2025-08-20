<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add indexes to optimize frequent queries and reduce N+1 issues

        // Answers table - for patient_id + question_id lookups
        Schema::table('answers', function (Blueprint $table) {
            $table->index(['patient_id', 'question_id'], 'idx_answers_patient_question');
        });

        // Patient status table - for patient_id + key lookups
        Schema::table('patient_status', function (Blueprint $table) {
            $table->index(['patient_id', 'key'], 'idx_patient_status_patient_key');
        });

        // Section field mappings - for field_name lookups
        Schema::table('section_field_mappings', function (Blueprint $table) {
            $table->index('field_name', 'idx_section_field_mappings_field_name');
        });

        // Questions table - for section_id + sort ordering
        Schema::table('questions', function (Blueprint $table) {
            $table->index(['section_id', 'sort'], 'idx_questions_section_sort');
        });

        // Patients table - for doctor_id and hidden status
        Schema::table('patients', function (Blueprint $table) {
            $table->index(['doctor_id', 'hidden'], 'idx_patients_doctor_hidden');
        });

        // Feed posts table - for group_id and created_at ordering
        Schema::table('feed_posts', function (Blueprint $table) {
            $table->index(['group_id', 'created_at'], 'idx_feed_posts_group_created');
        });

        // Notifications table - for doctor_id and created_at
        Schema::table('app_notifications', function (Blueprint $table) {
            $table->index(['doctor_id', 'created_at'], 'idx_notifications_doctor_created');
        });

        // Consultation doctors table - for consultation_id
        Schema::table('consultation_doctors', function (Blueprint $table) {
            $table->index('consultation_id', 'idx_consultation_doctors_consultation');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop the indexes in reverse order

        Schema::table('consultation_doctors', function (Blueprint $table) {
            $table->dropIndex('idx_consultation_doctors_consultation');
        });

        Schema::table('app_notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_doctor_created');
        });

        Schema::table('feed_posts', function (Blueprint $table) {
            $table->dropIndex('idx_feed_posts_group_created');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('idx_patients_doctor_hidden');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('idx_questions_section_sort');
        });

        Schema::table('section_field_mappings', function (Blueprint $table) {
            $table->dropIndex('idx_section_field_mappings_field_name');
        });

        Schema::table('patient_status', function (Blueprint $table) {
            $table->dropIndex('idx_patient_status_patient_key');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->dropIndex('idx_answers_patient_question');
        });
    }
};
