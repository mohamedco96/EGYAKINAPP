<?php

/**
 * Test script for name formatting to prevent duplicate "Dr." prefixes
 * Run: php scripts/test-name-formatting.php
 */

require_once __DIR__.'/../vendor/autoload.php';

use App\Traits\FormatsUserName;

class NameFormattingTester
{
    use FormatsUserName;

    public function runTests()
    {
        echo "🧪 Testing Name Formatting Fix\n";
        echo "==============================\n\n";

        $testCases = [
            // Test cases with names that already have prefixes
            [
                'name' => 'Dr. Mohamed Ibrahim',
                'lname' => '',
                'isSyndicateCardRequired' => 'Verified',
                'expected' => 'Dr. Mohamed Ibrahim',
                'description' => 'Name already has "Dr." prefix',
            ],
            [
                'name' => 'د. Mohamed Ibrahim',
                'lname' => '',
                'isSyndicateCardRequired' => 'Verified',
                'expected' => 'د. Mohamed Ibrahim',
                'description' => 'Name already has Arabic "د." prefix',
            ],
            [
                'name' => 'Dr Mohamed Ibrahim',
                'lname' => '',
                'isSyndicateCardRequired' => 'Verified',
                'expected' => 'Dr Mohamed Ibrahim',
                'description' => 'Name already has "Dr" prefix (no dot)',
            ],
            [
                'name' => 'Doctor Mohamed Ibrahim',
                'lname' => '',
                'isSyndicateCardRequired' => 'Verified',
                'expected' => 'Doctor Mohamed Ibrahim',
                'description' => 'Name already has "Doctor" prefix',
            ],
            // Test cases with names that need prefixes
            [
                'name' => 'Mohamed Ibrahim',
                'lname' => '',
                'isSyndicateCardRequired' => 'Verified',
                'expected' => 'Dr. Mohamed Ibrahim',
                'description' => 'Name needs "Dr." prefix (verified user)',
            ],
            [
                'name' => 'Mohamed',
                'lname' => 'Ibrahim',
                'isSyndicateCardRequired' => 'Verified',
                'expected' => 'Dr. Mohamed Ibrahim',
                'description' => 'Split name needs "Dr." prefix (verified user)',
            ],
            [
                'name' => 'Mohamed Ibrahim',
                'lname' => '',
                'isSyndicateCardRequired' => 'Pending',
                'expected' => 'Mohamed Ibrahim',
                'description' => 'Unverified user should not get prefix',
            ],
            [
                'name' => 'Mohamed Ibrahim',
                'lname' => '',
                'isSyndicateCardRequired' => null,
                'expected' => 'Mohamed Ibrahim',
                'description' => 'User with null verification should not get prefix',
            ],
            // Edge cases
            [
                'name' => 'Dr.Mohamed',
                'lname' => 'Ibrahim',
                'isSyndicateCardRequired' => 'Verified',
                'expected' => 'Dr.Mohamed Ibrahim',
                'description' => 'Name with "Dr." but no space',
            ],
            [
                'name' => 'د.محمد',
                'lname' => 'ابراهيم',
                'isSyndicateCardRequired' => 'Verified',
                'expected' => 'د.محمد ابراهيم',
                'description' => 'Arabic name with prefix but no space',
            ],
        ];

        $passed = 0;
        $failed = 0;

        foreach ($testCases as $i => $test) {
            $user = (object) $test;
            $result = $this->formatUserName($user);

            $status = ($result === $test['expected']) ? '✅ PASS' : '❌ FAIL';

            if ($result === $test['expected']) {
                $passed++;
            } else {
                $failed++;
            }

            echo sprintf(
                "%d. %s\n   Input: '%s %s' (Status: %s)\n   Expected: '%s'\n   Got: '%s'\n   %s\n\n",
                $i + 1,
                $test['description'],
                $test['name'],
                $test['lname'] ?? '',
                $test['isSyndicateCardRequired'] ?? 'null',
                $test['expected'],
                $result,
                $status
            );
        }

        echo "📊 TEST RESULTS\n";
        echo "===============\n";
        echo "✅ Passed: {$passed}\n";
        echo "❌ Failed: {$failed}\n";
        echo '📋 Total: '.($passed + $failed)."\n\n";

        if ($failed === 0) {
            echo "🎉 ALL TESTS PASSED! The duplicate prefix issue is fixed.\n";
        } else {
            echo "⚠️  Some tests failed. Please check the implementation.\n";
        }

        // Test the static method too
        echo "\n🧪 Testing Static Method\n";
        echo "========================\n";

        $staticTestUser = (object) [
            'name' => 'Dr. Test User',
            'lname' => '',
            'isSyndicateCardRequired' => 'Verified',
        ];

        $staticResult = FormatsUserName::getFormattedUserName($staticTestUser);
        $staticExpected = 'Dr. Test User';

        echo 'Static method test: '.($staticResult === $staticExpected ? '✅ PASS' : '❌ FAIL')."\n";
        echo "Expected: '{$staticExpected}'\n";
        echo "Got: '{$staticResult}'\n";
    }

    /**
     * Test the prefix detection method directly
     */
    public function testPrefixDetection()
    {
        echo "\n🔍 Testing Prefix Detection\n";
        echo "============================\n";

        $prefixTests = [
            ['Dr. Mohamed', true, 'English "Dr." with dot and space'],
            ['Dr Mohamed', true, 'English "Dr" without dot but with space'],
            ['dr. Mohamed', true, 'Lowercase "dr." with dot and space'],
            ['DR. Mohamed', true, 'Uppercase "DR." with dot and space'],
            ['د. محمد', true, 'Arabic "د." with dot and space'],
            ['د محمد', true, 'Arabic "د" without dot but with space'],
            ['Doctor Mohamed', true, 'Full "Doctor" prefix'],
            ['doctor Mohamed', true, 'Lowercase "doctor" prefix'],
            ['Mohamed Ibrahim', false, 'No prefix'],
            ['Dragan Mohamed', false, 'Name starting with "Dr" but not prefix'],
            ['Ahmed Dr. Mohamed', false, 'Dr. in middle of name'],
        ];

        foreach ($prefixTests as $i => $test) {
            [$name, $expected, $description] = $test;
            $result = $this->hasDoctoralPrefix($name);
            $status = ($result === $expected) ? '✅ PASS' : '❌ FAIL';

            echo sprintf(
                "%d. %s\n   Input: '%s'\n   Expected: %s\n   Got: %s\n   %s\n\n",
                $i + 1,
                $description,
                $name,
                $expected ? 'true' : 'false',
                $result ? 'true' : 'false',
                $status
            );
        }
    }
}

// Run the tests
$tester = new NameFormattingTester();
$tester->runTests();
$tester->testPrefixDetection();

echo "\n📋 SUMMARY\n";
echo "==========\n";
echo "This fix prevents duplicate doctor prefixes in push notifications by:\n";
echo "1. Checking if a name already has 'Dr.' or 'د.' prefix\n";
echo "2. Only adding prefix for verified users who don't already have one\n";
echo "3. Supporting both English and Arabic prefixes\n";
echo "4. Handling edge cases like missing spaces or dots\n\n";

echo "🚀 DEPLOYMENT\n";
echo "=============\n";
echo "The fix is now applied to the FormatsUserName trait.\n";
echo "All push notifications will use the corrected formatting.\n";
echo "No duplicate 'د. Dr.' or 'Dr. Dr.' prefixes will appear.\n";
