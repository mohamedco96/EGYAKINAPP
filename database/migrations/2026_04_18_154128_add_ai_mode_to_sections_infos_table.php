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
            // Allowed values: 'voice', 'image', 'files' — or null if AI is not configured for this section
            $table->string('ai_mode')->nullable()->after('section_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections_infos', function (Blueprint $table) {
            $table->dropColumn('ai_mode');
        });
    }
};
