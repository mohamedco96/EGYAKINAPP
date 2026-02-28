<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change column type to string first, then convert existing boolean values
        Schema::table('users', function (Blueprint $table) {
            $table->string('isSyndicateCardRequired')->default('Not Required')->change();
        });

        DB::statement("UPDATE users SET isSyndicateCardRequired = 'Not Required' WHERE isSyndicateCardRequired = '0' OR isSyndicateCardRequired = '' OR isSyndicateCardRequired IS NULL");
        DB::statement("UPDATE users SET isSyndicateCardRequired = 'Verified' WHERE isSyndicateCardRequired = '1'");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('isSyndicateCardRequired')->default(false)->change();
        });
    }
};
