<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $exists = collect(DB::select("SHOW COLUMNS FROM notifications LIKE 'type_id'"))->isNotEmpty();
        if (!$exists) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->unsignedBigInteger('type_id')->nullable()->after('type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('type_id');
        });
    }
};
