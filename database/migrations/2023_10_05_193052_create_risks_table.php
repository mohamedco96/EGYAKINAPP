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
        Schema::create('risks', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('owner_id')->unsigned()->index();
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('patient_id')->unsigned()->index()->nullable();
            $table->foreign('patient_id')->references('id')->on('patient_histories')->onDelete('cascade');
            $table->string('CKD_history')->nullable();
            $table->string('AK_history')->nullable();
            $table->string('cardiac-failure_history')->nullable();
            $table->string('LCF_history')->nullable();
            $table->string('neurological-impairment_disability_history')->nullable();
            $table->string('sepsis_history')->nullable();
            $table->string('contrast_media')->nullable();
            $table->string('drugs-with-potential-nephrotoxicity')->nullable();
            $table->string('drug_name')->nullable();
            $table->string('hypovolemia_history')->nullable();
            $table->string('malignancy_history')->nullable();
            $table->string('trauma_history')->nullable();
            $table->string('autoimmune-disease_history')->nullable();
            $table->string('other-risk-factors')->nullable();
            $table->longText('other')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risks');
    }
};
