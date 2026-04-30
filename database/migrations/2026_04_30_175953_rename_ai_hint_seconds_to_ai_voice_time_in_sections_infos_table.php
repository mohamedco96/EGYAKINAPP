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
        Schema::table('sections_infos', function (Blueprint $table) {
            $table->renameColumn('ai_hint_seconds', 'ai_voice_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections_infos', function (Blueprint $table) {
            $table->renameColumn('ai_voice_time', 'ai_hint_seconds');
        });
    }
};
