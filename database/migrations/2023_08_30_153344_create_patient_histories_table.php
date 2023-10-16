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
        Schema::create('patient_histories', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('doctor_id')->unsigned()->index();
            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('section_id')->unsigned()->index()->default(1);
            $table->string('name');
            $table->string('hospital');
            $table->string('collected_data_from');
            $table->string('NID')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('age');
            $table->string('gender');
            $table->string('occupation');
            $table->string('residency');
            $table->string('governorate');
            $table->string('marital_status');
            $table->string('educational_level');
            $table->string('special_habits_of_the_patient');
            $table->string('DM');
            $table->string('DM_duration')->nullable();
            $table->string('HTN');
            $table->string('HTN_duration')->nullable();
            $table->longText('other')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_histories');
    }
};
