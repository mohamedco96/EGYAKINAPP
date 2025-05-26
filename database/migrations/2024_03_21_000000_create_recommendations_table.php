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
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')
                ->constrained('patients')
                ->onDelete('cascade');
            $table->string('dose_name', 255);
            $table->string('dose', 255);
            $table->string('route', 100);
            $table->string('frequency', 100);
            $table->string('duration', 100);
            $table->timestamps();

            // Add indexes for frequently queried fields
            $table->index('patient_id');
            $table->index('dose_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
