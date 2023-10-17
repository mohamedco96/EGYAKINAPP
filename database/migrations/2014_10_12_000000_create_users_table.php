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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('lname')->nullable();
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->integer('age')->nullable();
            $table->string('specialty')->nullable();
            $table->string('workingplace')->nullable();
            $table->string('phone')->nullable();
            $table->string('job')->nullable();
            $table->string('highestdegree')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
