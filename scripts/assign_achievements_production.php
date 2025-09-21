<?php

/**
 * Production Script: Assign Achievements to All Users
 *
 * This script can be run directly on the production server to assign
 * achievements to all existing users based on their current data.
 *
 * Usage: php scripts/assign_achievements_production.php
 */

require_once __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🏆 EGYAKIN Achievement Assignment Script\n";
echo "=====================================\n\n";

try {
    // Get the achievement service
    $achievementService = $app->make(App\Modules\Achievements\Services\AchievementService::class);

    echo "📊 Checking current system status...\n";

    // Get some stats first
    $totalUsers = App\Models\User::count();
    $usersWithAchievements = App\Models\User::has('achievements')->count();
    $totalAchievements = App\Modules\Achievements\Models\Achievement::count();

    echo "   - Total Users: {$totalUsers}\n";
    echo "   - Users with Achievements: {$usersWithAchievements}\n";
    echo "   - Total Available Achievements: {$totalAchievements}\n\n";

    echo "🚀 Starting achievement assignment process...\n";
    echo "   This may take a few minutes for large datasets...\n\n";

    $startTime = microtime(true);

    // Run the achievement assignment
    $result = $achievementService->checkAndAssignAchievementsForAllUsers();

    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);

    if ($result['value']) {
        echo "✅ SUCCESS: {$result['message']}\n";
        echo "⏱️  Execution Time: {$executionTime} seconds\n\n";

        // Get updated stats
        $updatedUsersWithAchievements = App\Models\User::has('achievements')->count();
        $newUsersWithAchievements = $updatedUsersWithAchievements - $usersWithAchievements;

        echo "📈 Results:\n";
        echo "   - Users with Achievements (Before): {$usersWithAchievements}\n";
        echo "   - Users with Achievements (After): {$updatedUsersWithAchievements}\n";
        echo "   - New Users Assigned Achievements: {$newUsersWithAchievements}\n\n";

        echo "🎉 Achievement assignment completed successfully!\n";

    } else {
        echo "❌ ERROR: {$result['message']}\n";
        exit(1);
    }

} catch (Exception $e) {
    echo '💥 FATAL ERROR: '.$e->getMessage()."\n";
    echo "Stack trace:\n".$e->getTraceAsString()."\n";
    exit(1);
}

echo "\n✨ Script completed successfully!\n";
