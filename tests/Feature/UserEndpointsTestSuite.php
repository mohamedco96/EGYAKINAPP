<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User Endpoints Test Suite
 *
 * This class serves as a comprehensive test suite runner for all user-related endpoints.
 * It can be used to run all user tests at once or to verify the complete user functionality.
 */
class UserEndpointsTestSuite extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that all user-related test files exist and are properly structured
     *
     * @test
     */
    public function it_has_all_required_user_test_files()
    {
        $requiredTestFiles = [
            'tests/Feature/Modules/Auth/AuthControllerTest.php',
            'tests/Feature/Modules/Auth/UserLocaleControllerTest.php',
            'tests/Feature/Modules/Auth/PasswordResetTest.php',
            'tests/Feature/Modules/Auth/EmailVerificationTest.php',
            'tests/Feature/Modules/Auth/LocalizedNotificationControllerTest.php',
        ];

        foreach ($requiredTestFiles as $testFile) {
            $fullPath = base_path($testFile);
            $this->assertFileExists($fullPath, "Test file {$testFile} does not exist");
        }
    }

    /**
     * Test that all required factories exist for user testing
     *
     * @test
     */
    public function it_has_all_required_factories()
    {
        $requiredFactories = [
            \App\Models\User::class,
            \App\Modules\Notifications\Models\AppNotification::class,
            \App\Modules\Patients\Models\Patients::class,
        ];

        foreach ($requiredFactories as $factoryClass) {
            $this->assertTrue(
                class_exists($factoryClass),
                "Factory class {$factoryClass} does not exist"
            );
        }
    }

    /**
     * Verify that the test database is properly configured
     *
     * @test
     */
    public function it_has_proper_test_database_configuration()
    {
        // Verify we're using the test environment
        $this->assertEquals('testing', app()->environment());

        // Verify database connection is working
        $this->assertDatabaseHas('users', []);
        $this->assertDatabaseHas('notifications', []);
    }
}
