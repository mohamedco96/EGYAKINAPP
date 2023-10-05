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
        Schema::create('examinations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('owner_id')->unsigned()->index();
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('patient_id')->unsigned()->index()->nullable();
            $table->foreign('patient_id')->references('id')->on('patient_histories')->onDelete('cascade');
            $table->string('current_creatinine')->nullable();
            $table->string('basal_creatinine')->nullable();
            $table->string('renal_US')->nullable();
            $table->string('specify_renal-US')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examinations');
    }
};
