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
        Schema::create('patient_section_ai_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('sections_infos')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();

            // What type of input triggered the AI (audio transcription or image/PDF analysis)
            $table->enum('input_type', ['audio', 'image']);

            // Raw text extracted from the input (Whisper transcript or image analysis output)
            $table->text('extracted_text');

            // Full prompt constructed and sent to the AI model
            $table->text('prompt');

            // Structured AI response: JSON array of { question_id, question, value, type, ... }
            $table->json('response');

            // Indexes for common AI-trainer queries
            $table->index(['section_id', 'input_type']);
            $table->index(['patient_id', 'section_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_section_ai_logs');
    }
};
