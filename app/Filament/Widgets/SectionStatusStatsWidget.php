<?php

namespace App\Filament\Widgets;

use App\Models\SectionsInfo;
use App\Modules\Patients\Models\Patients;
use App\Modules\Patients\Models\PatientStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SectionStatusStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Cache stats for 5 minutes to improve performance
        $stats = Cache::remember('section_status_stats', 300, function () {
            // Total sections available
            $totalSections = SectionsInfo::where('id', '<>', 8)->count();

            // Total section statuses recorded
            $totalSectionStatuses = PatientStatus::where('key', 'LIKE', 'section_%')->count();

            // Completed sections
            $completedSections = PatientStatus::where('key', 'LIKE', 'section_%')
                ->where('status', true)
                ->count();

            // Pending sections
            $pendingSections = $totalSectionStatuses - $completedSections;

            // Completion rate
            $completionRate = $totalSectionStatuses > 0
                ? round(($completedSections / $totalSectionStatuses) * 100, 1)
                : 0;

            // Patients with sections
            $patientsWithSections = PatientStatus::where('key', 'LIKE', 'section_%')
                ->distinct('patient_id')
                ->count();

            // Average sections per patient
            $avgSectionsPerPatient = $patientsWithSections > 0
                ? round($totalSectionStatuses / $patientsWithSections, 1)
                : 0;

            // Most active section
            $mostActiveSection = PatientStatus::select('key', DB::raw('count(*) as count'))
                ->where('key', 'LIKE', 'section_%')
                ->groupBy('key')
                ->orderByDesc('count')
                ->first();

            $mostActiveSectionName = null;
            $mostActiveSectionCount = 0;
            if ($mostActiveSection) {
                $sectionId = str_replace('section_', '', $mostActiveSection->key);
                $section = SectionsInfo::find($sectionId);
                $mostActiveSectionName = $section?->section_name ?? "Section {$sectionId}";
                $mostActiveSectionCount = $mostActiveSection->count;
            }

            // Recent activity (today)
            $todayActivity = PatientStatus::where('key', 'LIKE', 'section_%')
                ->whereDate('updated_at', today())
                ->count();

            // Weekly trend
            $thisWeekSections = PatientStatus::where('key', 'LIKE', 'section_%')
                ->where('updated_at', '>=', now()->startOfWeek())
                ->count();

            $lastWeekSections = PatientStatus::where('key', 'LIKE', 'section_%')
                ->whereBetween('updated_at', [
                    now()->subWeek()->startOfWeek(),
                    now()->subWeek()->endOfWeek(),
                ])
                ->count();

            $weeklyTrend = $lastWeekSections > 0
                ? round((($thisWeekSections - $lastWeekSections) / $lastWeekSections) * 100, 1)
                : 0;

            return compact(
                'totalSections', 'totalSectionStatuses', 'completedSections',
                'pendingSections', 'completionRate', 'patientsWithSections',
                'avgSectionsPerPatient', 'mostActiveSectionName', 'mostActiveSectionCount',
                'todayActivity', 'weeklyTrend'
            );
        });

        return [
            Stat::make('Total Section Records', number_format($stats['totalSectionStatuses']))
                ->description($stats['patientsWithSections'].' patients • '.$stats['avgSectionsPerPatient'].' avg per patient')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary')
                ->chart([15, 20, 18, 25, 22, 30, $stats['totalSectionStatuses']])
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20',
                ]),

            Stat::make('Completion Rate', $stats['completionRate'].'%')
                ->description($stats['completedSections'].' completed • '.$stats['pendingSections'].' pending')
                ->descriptionIcon($stats['completionRate'] >= 70 ? 'heroicon-m-check-circle' : 'heroicon-m-clock')
                ->color($stats['completionRate'] >= 70 ? 'success' : ($stats['completionRate'] >= 40 ? 'warning' : 'danger'))
                ->chart([45, 52, 48, 61, 58, 65, $stats['completionRate']])
                ->extraAttributes([
                    'class' => $stats['completionRate'] >= 70
                        ? 'bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20'
                        : 'bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20',
                ]),

            Stat::make('Today\'s Activity', number_format($stats['todayActivity']))
                ->description('Section updates today • '.($stats['weeklyTrend'] >= 0 ? '+' : '').$stats['weeklyTrend'].'% vs last week')
                ->descriptionIcon($stats['weeklyTrend'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($stats['weeklyTrend'] >= 0 ? 'success' : 'danger')
                ->chart([8, 12, 6, 15, 10, 18, $stats['todayActivity']])
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20',
                ]),

            Stat::make('Most Active Section', $stats['mostActiveSectionName'] ?? 'N/A')
                ->description($stats['mostActiveSectionCount'].' patient records')
                ->descriptionIcon('heroicon-m-star')
                ->color('info')
                ->chart([12, 18, 15, 24, 20, 28, $stats['mostActiveSectionCount']])
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20',
                ]),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
