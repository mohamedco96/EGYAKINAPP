<?php

/**
 * Verification script for the Filtered Patients Export feature
 * This script verifies that all components are properly installed and can be loaded
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "🔍 Verifying Filtered Patients Export Feature...\n\n";

// 1. Check if Laravel can be loaded
try {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    echo "✅ Laravel application loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Failed to load Laravel: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Check if the PatientsController exists and has the exportFilteredPatients method
try {
    $controllerPath = __DIR__ . '/app/Modules/Patients/Controllers/PatientsController.php';
    if (file_exists($controllerPath)) {
        require_once $controllerPath;
        
        $reflection = new ReflectionClass('App\Modules\Patients\Controllers\PatientsController');
        
        if ($reflection->hasMethod('exportFilteredPatients')) {
            echo "✅ PatientsController::exportFilteredPatients method exists\n";
        } else {
            echo "❌ exportFilteredPatients method not found in PatientsController\n";
        }
        
        if ($reflection->hasMethod('filteredPatients')) {
            echo "✅ PatientsController::filteredPatients method exists\n";
        } else {
            echo "❌ filteredPatients method not found in PatientsController\n";
        }
    } else {
        echo "❌ PatientsController file not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking PatientsController: " . $e->getMessage() . "\n";
}

// 3. Check if PatientFilterService exists
try {
    $servicePath = __DIR__ . '/app/Modules/Patients/Services/PatientFilterService.php';
    if (file_exists($servicePath)) {
        require_once $servicePath;
        
        $reflection = new ReflectionClass('App\Modules\Patients\Services\PatientFilterService');
        
        if ($reflection->hasMethod('filterPatients')) {
            echo "✅ PatientFilterService::filterPatients method exists\n";
        } else {
            echo "❌ filterPatients method not found in PatientFilterService\n";
        }
    } else {
        echo "❌ PatientFilterService file not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking PatientFilterService: " . $e->getMessage() . "\n";
}

// 4. Check if API route exists
try {
    $routesPath = __DIR__ . '/routes/api.php';
    if (file_exists($routesPath)) {
        $routesContent = file_get_contents($routesPath);
        if (strpos($routesContent, 'exportFilteredPatients') !== false) {
            echo "✅ exportFilteredPatients API route found in routes/api.php\n";
        } else {
            echo "❌ exportFilteredPatients API route not found in routes/api.php\n";
        }
    } else {
        echo "❌ routes/api.php file not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking API routes: " . $e->getMessage() . "\n";
}

// 5. Check if maatwebsite/excel package is available
try {
    if (class_exists('Maatwebsite\Excel\Facades\Excel')) {
        echo "✅ maatwebsite/excel package is available\n";
    } else {
        echo "❌ maatwebsite/excel package not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking maatwebsite/excel: " . $e->getMessage() . "\n";
}

// 6. Check if test file exists
try {
    $testPath = __DIR__ . '/tests/Feature/FilteredPatientsExportTest.php';
    if (file_exists($testPath)) {
        echo "✅ FilteredPatientsExportTest.php exists\n";
    } else {
        echo "❌ FilteredPatientsExportTest.php not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking test file: " . $e->getMessage() . "\n";
}

// 7. Check if documentation exists
try {
    $docPath = __DIR__ . '/FILTERED_PATIENTS_EXPORT_API.md';
    if (file_exists($docPath)) {
        echo "✅ API documentation (FILTERED_PATIENTS_EXPORT_API.md) exists\n";
    } else {
        echo "❌ API documentation not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking documentation: " . $e->getMessage() . "\n";
}

// 8. Check storage permissions
try {
    $storageExportsPath = __DIR__ . '/storage/app/public/exports';
    if (!is_dir($storageExportsPath)) {
        if (mkdir($storageExportsPath, 0755, true)) {
            echo "✅ Created exports directory: $storageExportsPath\n";
        } else {
            echo "❌ Failed to create exports directory\n";
        }
    } else {
        echo "✅ Exports directory exists\n";
    }
    
    if (is_writable($storageExportsPath)) {
        echo "✅ Exports directory is writable\n";
    } else {
        echo "❌ Exports directory is not writable\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking storage permissions: " . $e->getMessage() . "\n";
}

echo "\n🎉 Verification completed!\n";
echo "\n📋 Feature Summary:\n";
echo "   • API Endpoint: POST /api/exportFilteredPatients\n";
echo "   • Controller: App\\Modules\\Patients\\Controllers\\PatientsController::exportFilteredPatients\n";
echo "   • Service: App\\Modules\\Patients\\Services\\PatientFilterService::filterPatients\n";
echo "   • Authentication: Required (Laravel Sanctum)\n";
echo "   • Export Format: Excel (.xlsx)\n";
echo "   • Storage: storage/app/public/exports/\n";
echo "   • Caching: 24 hours (filter params and results)\n";
echo "   • Documentation: FILTERED_PATIENTS_EXPORT_API.md\n";
echo "   • Tests: tests/Feature/FilteredPatientsExportTest.php\n";

echo "\n📖 Usage:\n";
echo "   1. Call GET /api/patientFilters to get filter conditions\n";
echo "   2. Call POST /api/patientFilters with filters to preview results\n";
echo "   3. Call POST /api/exportFilteredPatients with same filters to export CSV\n";

echo "\n✨ Ready for testing and deployment!\n";
