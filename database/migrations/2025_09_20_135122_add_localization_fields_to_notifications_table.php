<?php

use App\Database\Concerns\HasIndexHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use HasIndexHelpers;
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
            $table->dropIndex(['localization_key']);
            $table->dropColumn(['localization_key', 'localization_params']);
        });
    }

};
