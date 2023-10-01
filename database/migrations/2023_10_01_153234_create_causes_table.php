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
        Schema::create('causes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('owner_id')->unsigned()->index();
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('patient_id')->unsigned()->index()->nullable();
            $table->foreign('patient_id')->references('id')->on('patient_histories')->onDelete('cascade');
            $table->string('cause_of_AKI')->nullable();
            $table->string('pre-renal_causes')->nullable();
            $table->string('pre-renal_others')->nullable();
            $table->string('renal_causes')->nullable();
            $table->string('renal_others')->nullable();
            $table->string('post-renal_causes')->nullable();
            $table->string('post-renal_others')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('causes');
    }
};
