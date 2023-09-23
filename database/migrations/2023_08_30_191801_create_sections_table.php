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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('owner_id')->unsigned()->index();
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('patient_id')->unsigned()->index()->nullable();
            $table->foreign('patient_id')->references('id')->on('patient_histories')->onDelete('cascade');
            $table->boolean('section_1')->default(false);
            $table->boolean('section_2')->default(false);
            $table->boolean('section_3')->default(false);
            $table->boolean('section_4')->default(false);
            $table->boolean('section_5')->default(false);
            $table->boolean('section_6')->default(false);
            $table->boolean('section_7')->default(false);
            $table->boolean('submit_status')->default(false);
            $table->boolean('outcome_status')->default(false);
            $table->bigInteger('doc_id')->unsigned()->index()->nullable();
            $table->foreign('doc_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
