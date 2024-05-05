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
            $table->string('image')->nullable(); // New column for profile image
            $table->string('email');
            $table->string('age')->nullable();
            $table->string('specialty')->nullable();
            $table->string('workingplace')->nullable();
            $table->string('phone')->nullable();
            $table->string('job')->nullable();
            $table->string('gender')->nullable();
            $table->string('syndicate_card')->nullable();
            $table->string('birth_date')->nullable();
            $table->string('role')->nullable();
            $table->string('highestdegree')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->boolean('blocked')->default(false);
            $table->boolean('limited')->default(false);
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
