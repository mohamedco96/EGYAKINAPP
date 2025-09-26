<?php

namespace App\Filament\Widgets;

use App\Modules\Doses\Models\Dose;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DoseStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Cache stats for 5 minutes to improve performance
        $stats = Cache::remember('dose_stats', 300, function () {
            // Total doses
            $totalDoses = Dose::count();

            // Recent doses (last 30 days)
            $recentDoses = Dose::where('created_at', '>=', now()->subDays(30))->count();

            // This week vs last week
            $thisWeekDoses = Dose::where('created_at', '>=', now()->startOfWeek())->count();
            $lastWeekDoses = Dose::whereBetween('created_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek(),
            ])->count();

            $weeklyTrend = $lastWeekDoses > 0
                ? round((($thisWeekDoses - $lastWeekDoses) / $lastWeekDoses) * 100, 1)
                : ($thisWeekDoses > 0 ? 100 : 0);

            // Average content length
            $avgDescriptionLength = Dose::whereNotNull('description')
                ->avg(DB::raw('LENGTH(description)')) ?? 0;

            // Most recent dose
            $latestDose = Dose::latest()->first();

            // Doses with/without description
            $dosesWithDescription = Dose::whereNotNull('description')
                ->where('description', '!=', '')
                ->count();

            // Monthly growth data for charts (last 6 months)
            $monthlyData = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $monthlyData[] = Dose::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();
            }

            return compact(
                'totalDoses', 'recentDoses', 'weeklyTrend', 'avgDescriptionLength',
                'latestDose', 'dosesWithDescription', 'monthlyData', 'thisWeekDoses'
            );
        });

        return [
            Stat::make('Total Dose Modifiers', number_format($stats['totalDoses']))
                ->description($stats['recentDoses'].' added in last 30 days')
                ->descriptionIcon('heroicon-m-beaker')
                ->color('primary')
                ->chart($stats['monthlyData'])
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20',
                ]),

            Stat::make('Weekly Activity', number_format($stats['thisWeekDoses']))
                ->description(($stats['weeklyTrend'] >= 0 ? '+' : '').$stats['weeklyTrend'].'% vs last week')
                ->descriptionIcon($stats['weeklyTrend'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($stats['weeklyTrend'] >= 0 ? 'success' : 'danger')
                ->chart(array_slice($stats['monthlyData'], -4))
                ->extraAttributes([
                    'class' => $stats['weeklyTrend'] >= 0
                        ? 'bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20'
                        : 'bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20',
                ]),

            Stat::make('Content Quality', round($stats['avgDescriptionLength']).' chars avg')
                ->description($stats['dosesWithDescription'].' of '.$stats['totalDoses'].' have descriptions')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->chart([
                    $stats['totalDoses'] - $stats['dosesWithDescription'],
                    $stats['dosesWithDescription'],
                ])
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20',
                ]),

            Stat::make('Latest Addition', $stats['latestDose'] ? 'Today' : 'None')
                ->description($stats['latestDose']
                    ? 'Last: '.str($stats['latestDose']->title)->limit(20)
                    : 'No doses added yet')
                ->descriptionIcon('heroicon-m-clock')
                ->color($stats['latestDose'] ? 'warning' : 'gray')
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20',
                ]),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
