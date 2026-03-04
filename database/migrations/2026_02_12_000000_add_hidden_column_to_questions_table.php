<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $exists = collect(DB::select("SHOW COLUMNS FROM questions LIKE 'hidden'"))->isNotEmpty();
        if (!$exists) {
            Schema::table('questions', function (Blueprint $table) {
                $table->boolean('hidden')->default(false)->after('sort');
            });
        }
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('hidden');
        });
    }
};
