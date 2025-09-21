<?php

/**
 * Verification Script: Check Achievement Assignment Results
 *
 * Run this after the achievement assignment to verify results
 *
 * Usage: php scripts/verify_achievements.php
 */

require_once __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 EGYAKIN Achievement Verification Report\n";
echo "=========================================\n\n";

try {
    // Get achievements
    $achievements = App\Modules\Achievements\Models\Achievement::all();

    echo "📋 Available Achievements:\n";
    foreach ($achievements as $achievement) {
        echo "   - {$achievement->name} ({$achievement->type}: {$achievement->score})\n";
    }
    echo "\n";

    // Get users with high patient counts who should have achievements
    $usersWithManyPatients = App\Models\User::with(['patients', 'achievements', 'score'])
        ->get()
        ->filter(function ($user) {
            return $user->patients->count() >= 10; // Users who should have at least one achievement
        })
        ->sortByDesc(function ($user) {
            return $user->patients->count();
        });

    echo "👥 Top Users by Patient Count (Should Have Achievements):\n";
    echo "--------------------------------------------------------\n";

    foreach ($usersWithManyPatients->take(10) as $user) {
        $patientCount = $user->patients->count();
        $score = $user->score?->score ?? 0;
        $achievementCount = $user->achievements->count();

        echo sprintf(
            "%-20s | Patients: %3d | Score: %3d | Achievements: %d\n",
            substr($user->name, 0, 18),
            $patientCount,
            $score,
            $achievementCount
        );

        if ($achievementCount > 0) {
            foreach ($user->achievements as $achievement) {
                echo "                       └─ ✅ {$achievement->name}\n";
            }
        } else {
            echo "                       └─ ❌ No achievements assigned\n";
        }
    }

    echo "\n📊 Summary Statistics:\n";
    echo "---------------------\n";

    $totalUsers = App\Models\User::count();
    $usersWithAchievements = App\Models\User::has('achievements')->count();
    $usersWithPatients = App\Models\User::has('patients')->count();
    $usersWhoShouldHaveAchievements = App\Models\User::whereHas('patients', function ($query) {
        $query->havingRaw('COUNT(*) >= 10');
    })->count();

    echo "Total Users: {$totalUsers}\n";
    echo "Users with Patients: {$usersWithPatients}\n";
    echo "Users with 10+ Patients (should have achievements): {$usersWhoShouldHaveAchievements}\n";
    echo "Users with Achievements: {$usersWithAchievements}\n";

    if ($usersWhoShouldHaveAchievements > 0) {
        $coveragePercentage = round(($usersWithAchievements / $usersWhoShouldHaveAchievements) * 100, 1);
        echo "Achievement Coverage: {$coveragePercentage}%\n";

        if ($coveragePercentage >= 90) {
            echo "✅ Achievement system is working well!\n";
        } elseif ($coveragePercentage >= 70) {
            echo "⚠️  Achievement system needs some attention\n";
        } else {
            echo "❌ Achievement system may have issues\n";
        }
    }

} catch (Exception $e) {
    echo '💥 ERROR: '.$e->getMessage()."\n";
    exit(1);
}

echo "\n✨ Verification completed!\n";
