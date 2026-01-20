<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasTable('polls')) {
            return;
        }

        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_post_id')->constrained('feed_posts')->onDelete('cascade');
            $table->string('question');
            $table->boolean('allow_add_options')->default(false);
            $table->boolean('allow_multiple_choice')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('polls');
    }
};

