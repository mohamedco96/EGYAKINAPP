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
            $table->unsignedInteger('ai_hint_seconds')->nullable()->after('ai_hint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections_infos', function (Blueprint $table) {
            $table->dropColumn('ai_hint_seconds');
        });
    }
};
