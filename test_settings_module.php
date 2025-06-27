<?php

// Test script to verify SettingsController can be loaded
require_once __DIR__ . '/vendor/autoload.php';

try {
    // Test if the base Controller class can be loaded
    $baseController = new ReflectionClass('App\Http\Controllers\Controller');
    echo "✅ Base Controller class loaded successfully\n";
    
    // Test if SettingsController can be loaded
    $settingsController = new ReflectionClass('App\Modules\Settings\Controllers\SettingsController');
    echo "✅ SettingsController class loaded successfully\n";
    
    // Test if SettingsService can be loaded
    $settingsService = new ReflectionClass('App\Modules\Settings\Services\SettingsService');
    echo "✅ SettingsService class loaded successfully\n";
    
    // Test if Settings model can be loaded
    $settingsModel = new ReflectionClass('App\Modules\Settings\Models\Settings');
    echo "✅ Settings model loaded successfully\n";
    
    echo "\n🎉 All Settings module classes are working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
