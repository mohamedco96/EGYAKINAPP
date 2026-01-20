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
                if (Schema::hasTable('consultation_replies')) {
            return;
        }

        Schema::create('consultation_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_doctor_id')->constrained('consultation_doctors')->onDelete('cascade');
            $table->text('reply');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_replies');
    }
};
