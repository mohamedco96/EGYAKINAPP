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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('section_id')->unsigned()->index();
            $table->foreign('section_id')->references('id')->on('sections_infos')->onDelete('cascade');
            $table->string('section_name');
            $table->text('question');
            $table->text('values')->nullable();
            $table->string('type');
            $table->string('keyboard_type')->nullable();
            $table->boolean('mandatory')->default(false);
            $table->boolean('skip')->nullable()->default(false);
            $table->integer('sort')->default(0); // New column for sorting
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
