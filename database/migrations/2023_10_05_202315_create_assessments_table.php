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
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('doctor_id')->unsigned()->index();
            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('patient_id')->unsigned()->index()->nullable();
            $table->foreign('patient_id')->references('id')->on('patient_histories')->onDelete('cascade');
            $table->string('heart-rate/minute')->nullable();
            $table->string('respiratory-rate/minute')->nullable();
            $table->string('SBP')->nullable();
            $table->string('DBP')->nullable();
            $table->string('GCS')->nullable();
            $table->string('oxygen_saturation')->nullable();
            $table->string('temperature')->nullable();
            $table->string('UOP')->nullable();
            $table->string('AVPU')->nullable();
            $table->string('skin_examination')->nullable();
            $table->string('skin_examination_clarify')->nullable();
            $table->string('eye_examination')->nullable();
            $table->string('eye_examination_clarify')->nullable();
            $table->string('ear_examination')->nullable();
            $table->string('ear_examination_clarify')->nullable();
            $table->string('cardiac_examination')->nullable();
            $table->string('cardiac_examination_clarify')->nullable();
            $table->string('internal_jugular_vein')->nullable();
            $table->string('chest_examination')->nullable();
            $table->string('chest_examination_clarify')->nullable();
            $table->string('abdominal_examination')->nullable();
            $table->string('abdominal_examination_clarify')->nullable();
            $table->string('other')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
