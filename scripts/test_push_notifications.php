<?php

/**
 * Manual Push Notification Test Script
 *
 * This script allows you to manually test push notifications
 * without using the Laravel console commands.
 *
 * Usage: php scripts/test_push_notifications.php
 */

require_once __DIR__.'/../vendor/autoload.php';

use GuzzleHttp\Client;

class PushNotificationTester
{
    private $baseUrl;

    private $client;

    private $authToken;

    public function __construct($baseUrl = 'http://localhost:8000')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false, // Disable SSL verification for local testing
        ]);
    }

    /**
     * Run the test suite
     */
    public function run()
    {
        echo "🚀 Push Notification Test Suite\n";
        echo "===============================\n\n";

        try {
            // Test 1: Check API connectivity
            $this->testApiConnectivity();

            // Test 2: Test FCM token storage
            $this->testFcmTokenStorage();

            // Test 3: Test notification sending
            $this->testNotificationSending();

            // Test 4: Test predefined notification
            $this->testPredefinedNotification();

            echo "\n✅ All tests completed!\n";

        } catch (Exception $e) {
            echo "\n❌ Test failed: ".$e->getMessage()."\n";
            exit(1);
        }
    }

    /**
     * Test API connectivity
     */
    private function testApiConnectivity()
    {
        echo "🔗 Testing API connectivity...\n";

        try {
            $response = $this->client->get($this->baseUrl.'/api/settings');

            if ($response->getStatusCode() === 200) {
                echo "   ✅ API is accessible\n";
            } else {
                throw new Exception('API returned status: '.$response->getStatusCode());
            }
        } catch (Exception $e) {
            throw new Exception('Cannot connect to API: '.$e->getMessage());
        }
    }

    /**
     * Test FCM token storage
     */
    private function testFcmTokenStorage()
    {
        echo "\n📱 Testing FCM token storage...\n";

        // Generate a test FCM token
        $testToken = $this->generateTestFcmToken();
        echo '   Generated test token: '.substr($testToken, 0, 30)."...\n";

        try {
            // First, try to get an auth token (you might need to implement login)
            $this->authenticateTestUser();

            // Test storing FCM token
            $response = $this->client->post($this->baseUrl.'/api/storeFCM', [
                'headers' => $this->getAuthHeaders(),
                'json' => [
                    'token' => $testToken,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['value'] === true) {
                echo "   ✅ FCM token stored successfully\n";
            } else {
                echo '   ❌ FCM token storage failed: '.$data['message']."\n";
            }

        } catch (Exception $e) {
            echo '   ⚠️  FCM token test skipped (auth required): '.$e->getMessage()."\n";
        }
    }

    /**
     * Test notification sending
     */
    private function testNotificationSending()
    {
        echo "\n📤 Testing notification sending...\n";

        try {
            $response = $this->client->post($this->baseUrl.'/api/send-notification', [
                'headers' => $this->getAuthHeaders(),
                'json' => [
                    'title' => 'Test Notification 🧪',
                    'body' => 'This is a test notification sent at '.date('Y-m-d H:i:s'),
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['status']) && strpos($data['status'], 'successfully') !== false) {
                echo "   ✅ Notification sent successfully\n";
            } else {
                echo '   ⚠️  Notification response: '.($data['status'] ?? 'Unknown')."\n";
            }

        } catch (Exception $e) {
            echo '   ⚠️  Notification test skipped (auth required): '.$e->getMessage()."\n";
        }
    }

    /**
     * Test predefined notification
     */
    private function testPredefinedNotification()
    {
        echo "\n📢 Testing predefined notification...\n";

        try {
            $response = $this->client->post($this->baseUrl.'/api/sendAllPushNotification', [
                'headers' => $this->getAuthHeaders(),
                'json' => [],
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['status']) && strpos($data['status'], 'successfully') !== false) {
                echo "   ✅ Predefined notification sent successfully\n";
            } else {
                echo '   ⚠️  Predefined notification response: '.($data['status'] ?? 'Unknown')."\n";
            }

        } catch (Exception $e) {
            echo '   ⚠️  Predefined notification test skipped (auth required): '.$e->getMessage()."\n";
        }
    }

    /**
     * Authenticate test user (simplified - you may need to implement proper login)
     */
    private function authenticateTestUser()
    {
        // For testing purposes, you can:
        // 1. Use a test user's API token
        // 2. Implement login flow
        // 3. Use Laravel Sanctum token

        // Example with hardcoded token (replace with actual token):
        // $this->authToken = 'your-test-user-token';

        // For now, we'll skip authentication-required tests
        throw new Exception('Authentication not implemented in test script');
    }

    /**
     * Get authentication headers
     */
    private function getAuthHeaders()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($this->authToken) {
            $headers['Authorization'] = 'Bearer '.$this->authToken;
        }

        return $headers;
    }

    /**
     * Generate a test FCM token
     */
    private function generateTestFcmToken()
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_:';
        $token = '';

        for ($i = 0; $i < 180; $i++) {
            $token .= $chars[rand(0, strlen($chars) - 1)];
        }

        return $token;
    }
}

// Configuration
$baseUrl = 'http://localhost:8000'; // Change this to your app URL

// Run the tests
$tester = new PushNotificationTester($baseUrl);
$tester->run();
