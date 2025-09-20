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
        Schema::table('notifications', function (Blueprint $table) {
            // Add localization fields
            $table->string('localization_key')->nullable()->after('content');
            $table->json('localization_params')->nullable()->after('localization_key');

            // Add index for better performance
            $table->index('localization_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['localization_key']);
            $table->dropColumn(['localization_key', 'localization_params']);
        });
    }
};
