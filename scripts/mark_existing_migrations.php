<?php

/**
 * Script to mark migrations as run for tables that already exist in the database.
 * This is useful when you have an existing database but the migrations table is empty.
 * 
 * Usage: php scripts/mark_existing_migrations.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$migrationsPath = __DIR__ . '/../database/migrations';
$migrationFiles = glob($migrationsPath . '/*.php');

$marked = 0;
$skipped = 0;

echo "🔍 Checking existing tables and marking migrations...\n\n";

foreach ($migrationFiles as $file) {
    $filename = basename($file);
    
    // Extract table name from migration filename
    // Pattern: YYYY_MM_DD_HHMMSS_create_table_name_table.php
    if (preg_match('/create_([a-z_]+)_table\.php$/i', $filename, $matches)) {
        $tableName = $matches[1];
        
        // Check if table exists
        if (Schema::hasTable($tableName)) {
            // Check if migration is already recorded
            $exists = DB::table('migrations')
                ->where('migration', pathinfo($filename, PATHINFO_FILENAME))
                ->exists();
            
            if (!$exists) {
                // Get the next batch number
                $batch = DB::table('migrations')->max('batch') ?? 0;
                $batch++;
                
                DB::table('migrations')->insert([
                    'migration' => pathinfo($filename, PATHINFO_FILENAME),
                    'batch' => $batch,
                ]);
                
                echo "✅ Marked: {$filename} (table: {$tableName})\n";
                $marked++;
            } else {
                echo "⏭️  Already marked: {$filename}\n";
                $skipped++;
            }
        }
    } else {
        // For non-create-table migrations, we can't auto-detect, so skip for now
        // These would need manual handling
    }
}

echo "\n📊 Summary:\n";
echo "   Marked: {$marked} migrations\n";
echo "   Skipped: {$skipped} migrations\n";
echo "\n✅ Done!\n";

