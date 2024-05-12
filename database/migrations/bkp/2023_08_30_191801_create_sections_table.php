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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('doctor_id')->unsigned()->index();
            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('patient_id')->unsigned()->index()->nullable();
            $table->foreign('patient_id')->references('id')->on('patient_histories')->onDelete('cascade');
            $table->boolean('section_1')->default(false);
            $table->boolean('section_2')->default(false);
            $table->boolean('section_3')->default(false);
            $table->boolean('section_4')->default(false);
            $table->boolean('section_5')->default(false);
            $table->boolean('section_6')->default(false);
            $table->boolean('section_7')->default(false);
            $table->boolean('section_8')->default(false);
            $table->boolean('section_9')->default(false);
            $table->boolean('section_10')->default(false);
            $table->boolean('section_11')->default(false);
            $table->boolean('section_12')->default(false);
            $table->boolean('section_13')->default(false);
            $table->boolean('section_14')->default(false);
            $table->boolean('section_15')->default(false);
            $table->boolean('submit_status')->default(false);
            $table->boolean('outcome_status')->default(false);
            $table->bigInteger('outcome_doctor_id')->unsigned()->index()->nullable();
            $table->foreign('outcome_doctor_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
