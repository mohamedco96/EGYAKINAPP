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
        $exists = collect(DB::select("SHOW COLUMNS FROM users LIKE 'version'"))->isNotEmpty();
        if (!$exists) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('version')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
