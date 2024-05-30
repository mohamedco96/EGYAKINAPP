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
            $table->string('name');
            $table->string('hospital');
            $table->string('collected_data_from');
            $table->string('NID')->unique();
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
            $table->string('special_habits_of_the_patient_other_field')->nullable();
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
