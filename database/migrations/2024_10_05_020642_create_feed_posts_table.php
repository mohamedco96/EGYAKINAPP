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
                if (Schema::hasTable('feed_posts')) {
            return;
        }

        Schema::create('feed_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->text('content')->nullable();
            $table->string('media_type')->nullable(); // 'image', 'video', etc.
            $table->string('media_path')->nullable(); // Path to the uploaded media file
            $table->enum('visibility', ['Public', 'Friends', 'Only Me'])->default('Public');
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //Schema::dropIfExists('feed_posts');
    }
};
