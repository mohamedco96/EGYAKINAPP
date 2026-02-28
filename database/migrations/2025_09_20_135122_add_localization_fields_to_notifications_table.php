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
            if (!Schema::hasColumn('notifications', 'localization_key')) {
                $table->string('localization_key')->nullable()->after('content');
            }
            if (!Schema::hasColumn('notifications', 'localization_params')) {
                $table->json('localization_params')->nullable()->after('localization_key');
            }

            // Add index for better performance
            if (!$this->indexExists('notifications', 'notifications_localization_key_index')) {
                $table->index('localization_key');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if ($this->indexExists('notifications', 'notifications_localization_key_index')) {
                $table->dropIndex(['localization_key']);
            }
            if (Schema::hasColumn('notifications', 'localization_key')) {
                $table->dropColumn('localization_key');
            }
            if (Schema::hasColumn('notifications', 'localization_params')) {
                $table->dropColumn('localization_params');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = array_column(Schema::getIndexes($table), null, 'name');

        return isset($indexes[$indexName]);
    }
};
