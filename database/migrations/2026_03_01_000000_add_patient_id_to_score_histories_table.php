<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $exists = collect(DB::select("SHOW COLUMNS FROM score_histories LIKE 'patient_id'"))->isNotEmpty();
        if (!$exists) {
            Schema::table('score_histories', function (Blueprint $table) {
                $table->unsignedBigInteger('patient_id')->nullable()->after('doctor_id');
                $table->foreign('patient_id')->references('id')->on('patients')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('score_histories', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn('patient_id');
        });
    }
};
