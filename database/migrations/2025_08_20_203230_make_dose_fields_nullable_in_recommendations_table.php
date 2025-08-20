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
        Schema::table('recommendations', function (Blueprint $table) {
            $table->string('dose_name', 255)->nullable()->change();
            $table->string('dose', 255)->nullable()->change();
            $table->string('route', 100)->nullable()->change();
            $table->string('frequency', 100)->nullable()->change();
            $table->string('duration', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->string('dose_name', 255)->nullable(false)->change();
            $table->string('dose', 255)->nullable(false)->change();
            $table->string('route', 100)->nullable(false)->change();
            $table->string('frequency', 100)->nullable(false)->change();
            $table->string('duration', 100)->nullable(false)->change();
        });
    }
};
