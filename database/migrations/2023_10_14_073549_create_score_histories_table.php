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
        if (Schema::hasTable('score_histories')) {
            if (! Schema::hasColumn('score_histories', 'patient_id')) {
                Schema::table('score_histories', function (Blueprint $table) {
                    $table->unsignedBigInteger('patient_id')->nullable()->after('doctor_id');
                    $table->index('patient_id');
                });
            }

            return;
        }

        Schema::create('score_histories', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('doctor_id')->unsigned()->index();
            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('patient_id')->nullable()->index();
            $table->string('score');
            $table->string('threshold')->default(0);
            $table->string('action');
            $table->timestamp('timestamp');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('score_histories');
    }
};
