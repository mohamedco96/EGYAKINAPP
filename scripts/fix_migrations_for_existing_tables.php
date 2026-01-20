<?php

/**
 * Script to automatically add table existence checks to all create table migrations.
 * This prevents errors when running migrations on a database that already has tables.
 * 
 * Usage: php scripts/fix_migrations_for_existing_tables.php
 */

$migrationsPath = __DIR__ . '/../database/migrations';
$migrationFiles = glob($migrationsPath . '/*_create_*.php');

$fixed = 0;
$skipped = 0;

echo "🔧 Fixing migrations to check for existing tables...\n\n";

foreach ($migrationFiles as $file) {
    $content = file_get_contents($file);
    $filename = basename($file);
    
    // Skip if already fixed
    if (strpos($content, 'Schema::hasTable') !== false) {
        echo "⏭️  Already fixed: {$filename}\n";
        $skipped++;
        continue;
    }
    
    // Find Schema::create calls
    if (preg_match('/Schema::create\([\'"]([^\'"]+)[\'"]/', $content, $matches)) {
        $tableName = $matches[1];
        
        // Find the up() method
        if (preg_match('/(public function up\(\): void\s*\{)\s*(Schema::create\([\'"]' . preg_quote($tableName, '/') . '[\'"]\s*,)/s', $content, $methodMatches)) {
            $checkCode = "        if (Schema::hasTable('{$tableName}')) {\n            return;\n        }\n\n        ";
            
            $newContent = str_replace(
                $methodMatches[1] . "\n        " . $methodMatches[2],
                $methodMatches[1] . "\n        " . $checkCode . $methodMatches[2],
                $content
            );
            
            file_put_contents($file, $newContent);
            echo "✅ Fixed: {$filename} (table: {$tableName})\n";
            $fixed++;
        } else {
            echo "⚠️  Could not parse: {$filename}\n";
        }
    }
}

echo "\n📊 Summary:\n";
echo "   Fixed: {$fixed} migrations\n";
echo "   Skipped: {$skipped} migrations\n";
echo "\n✅ Done! You can now run: php artisan migrate\n";

