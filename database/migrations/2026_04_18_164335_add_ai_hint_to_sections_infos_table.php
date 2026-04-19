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
            // HTML hint shown to the user before recording their voice for this section.
            // Null means no hint is configured for this section.
            $table->text('ai_hint')->nullable()->after('ai_mode');
        });
    }

    public function down(): void
    {
        Schema::table('sections_infos', function (Blueprint $table) {
            $table->dropColumn('ai_hint');
        });
    }
};
