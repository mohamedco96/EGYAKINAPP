<?php

// Test script to verify RolePermission module can be loaded
require_once __DIR__ . '/vendor/autoload.php';

try {
    // Test if RolePermissionController can be loaded
    $controller = new ReflectionClass('App\Modules\RolePermission\Controllers\RolePermissionController');
    echo "✅ RolePermissionController class loaded successfully\n";
    
    // Test if RolePermissionService can be loaded
    $service = new ReflectionClass('App\Modules\RolePermission\Services\RolePermissionService');
    echo "✅ RolePermissionService class loaded successfully\n";
    
    // Test if RolePermission model can be loaded
    $model = new ReflectionClass('App\Modules\RolePermission\Models\RolePermission');
    echo "✅ RolePermission model loaded successfully\n";
    
    // Test if Request classes can be loaded
    $createRequest = new ReflectionClass('App\Modules\RolePermission\Requests\CreateRoleAndPermissionRequest');
    echo "✅ CreateRoleAndPermissionRequest loaded successfully\n";
    
    $assignRequest = new ReflectionClass('App\Modules\RolePermission\Requests\AssignRoleToUserRequest');
    echo "✅ AssignRoleToUserRequest loaded successfully\n";
    
    // Test if Policy can be loaded
    $policy = new ReflectionClass('App\Modules\RolePermission\Policies\RolePermissionPolicy');
    echo "✅ RolePermissionPolicy loaded successfully\n";
    
    echo "\n🎉 All RolePermission module classes are working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    // Show file existence
    $files = [
        'Controllers/RolePermissionController.php',
        'Services/RolePermissionService.php',
        'Models/RolePermission.php',
        'Requests/CreateRoleAndPermissionRequest.php',
        'Requests/AssignRoleToUserRequest.php',
        'Policies/RolePermissionPolicy.php'
    ];
    
    echo "\nChecking file existence:\n";
    foreach ($files as $file) {
        $path = __DIR__ . '/app/Modules/RolePermission/' . $file;
        echo (file_exists($path) ? "✅ " : "❌ ") . $file . "\n";
    }
    
    exit(1);
}
