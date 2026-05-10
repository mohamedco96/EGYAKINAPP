<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_section_ai_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_id')->nullable()->change();
            $table->unsignedBigInteger('doctor_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('patient_section_ai_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_id')->nullable(false)->change();
            $table->unsignedBigInteger('doctor_id')->nullable(false)->change();
        });
    }
};
