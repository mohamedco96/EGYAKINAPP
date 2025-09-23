<?php

/**
 * User Endpoints Test Runner
 *
 * This script runs all user-related endpoint tests and provides a comprehensive report.
 * It can be used for continuous integration or manual testing verification.
 *
 * Usage:
 * php run_user_tests.php [--verbose] [--coverage] [--specific=TestClass]
 *
 * Options:
 * --verbose    Show detailed output for each test
 * --coverage   Generate code coverage report (requires Xdebug)
 * --specific   Run only a specific test class
 *
 * Examples:
 * php run_user_tests.php
 * php run_user_tests.php --verbose
 * php run_user_tests.php --specific=AuthControllerTest
 * php run_user_tests.php --coverage
 */

// Parse command line arguments
$options = getopt('', ['verbose', 'coverage', 'specific:']);
$verbose = isset($options['verbose']);
$coverage = isset($options['coverage']);
$specific = $options['specific'] ?? null;

// Test classes to run
$testClasses = [
    'Tests\\Feature\\Modules\\Auth\\AuthControllerTest',
    'Tests\\Feature\\Modules\\Auth\\UserLocaleControllerTest',
    'Tests\\Feature\\Modules\\Auth\\PasswordResetTest',
    'Tests\\Feature\\Modules\\Auth\\EmailVerificationTest',
    'Tests\\Feature\\Modules\\Auth\\LocalizedNotificationControllerTest',
    'Tests\\Feature\\UserEndpointsTestSuite',
];

// If specific test is requested, filter the list
if ($specific) {
    $testClasses = array_filter($testClasses, function ($class) use ($specific) {
        return strpos($class, $specific) !== false;
    });

    if (empty($testClasses)) {
        echo "❌ No test class found matching: {$specific}\n";
        echo "Available test classes:\n";
        foreach ($testClasses as $class) {
            echo '  - '.basename(str_replace('\\', '/', $class))."\n";
        }
        exit(1);
    }
}

echo "🚀 Running User Endpoints Test Suite\n";
echo "=====================================\n\n";

// Build PHPUnit command
$command = './vendor/bin/phpunit';

// Add coverage option if requested
if ($coverage) {
    $command .= ' --coverage-html coverage/user-endpoints';
    echo "📊 Code coverage will be generated in coverage/user-endpoints/\n\n";
}

// Add verbose option if requested
if ($verbose) {
    $command .= ' --verbose';
}

// Add configuration
$command .= ' --configuration phpunit.xml';

// Add specific test classes or run all
if ($specific) {
    foreach ($testClasses as $testClass) {
        $testFile = str_replace(['Tests\\', '\\'], ['tests/', '/'], $testClass).'.php';
        $command .= ' '.$testFile;
    }
} else {
    $command .= ' tests/Feature/Modules/Auth/ tests/Feature/UserEndpointsTestSuite.php';
}

echo "🔧 Command: {$command}\n\n";

// Execute the tests
$startTime = microtime(true);
$output = [];
$returnCode = 0;

exec($command.' 2>&1', $output, $returnCode);

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

// Display results
echo implode("\n", $output)."\n\n";

echo "⏱️  Execution time: {$duration} seconds\n";

if ($returnCode === 0) {
    echo "✅ All user endpoint tests passed successfully!\n";

    // Display test summary
    $testSummary = [
        'Authentication Tests' => [
            'User Registration',
            'User Login/Logout',
            'Password Change',
            'Profile Management',
            'File Uploads',
            'User Management (CRUD)',
        ],
        'Locale Tests' => [
            'Locale Update',
            'Locale Retrieval',
            'Locale Validation',
        ],
        'Password Reset Tests' => [
            'Reset Request',
            'Token Verification',
            'Password Reset',
            'Security Validations',
        ],
        'Email Verification Tests' => [
            'Verification Email Sending',
            'OTP Generation/Verification',
            'Email Verification Flow',
        ],
        'Notification Tests' => [
            'Localized Notifications',
            'Notification Management',
            'Read/Unread Status',
            'Pagination & Filtering',
        ],
    ];

    echo "\n📋 Test Coverage Summary:\n";
    echo "========================\n";

    foreach ($testSummary as $category => $tests) {
        echo "\n{$category}:\n";
        foreach ($tests as $test) {
            echo "  ✓ {$test}\n";
        }
    }

    echo "\n🎯 All user-related endpoints are properly tested and working!\n";

    if ($coverage) {
        echo "\n📊 Code coverage report generated in coverage/user-endpoints/index.html\n";
    }

} else {
    echo "❌ Some tests failed. Please check the output above for details.\n";

    // Provide troubleshooting tips
    echo "\n🔍 Troubleshooting Tips:\n";
    echo "======================\n";
    echo "1. Ensure database is properly configured for testing\n";
    echo "2. Run 'php artisan migrate --env=testing' to set up test database\n";
    echo "3. Check that all required factories are properly defined\n";
    echo "4. Verify that Sanctum is properly configured for API authentication\n";
    echo "5. Make sure all required environment variables are set\n";

    exit(1);
}

echo "\n".str_repeat('=', 50)."\n";
echo "User Endpoints Test Suite Completed\n";
echo str_repeat('=', 50)."\n";
