<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Modules\Patients\Models\Patients;
use App\Modules\Questions\Models\Questions;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PatientsStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Cache stats for 5 minutes to improve performance
        $stats = Cache::remember('patients_stats', 300, function () {
            $totalPatients = Patients::count();
            $activePatients = Patients::where('hidden', false)->count();
            $hiddenPatients = Patients::where('hidden', true)->count();

            // New patients this month
            $newPatientsThisMonth = Patients::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            // New patients today
            $newPatientsToday = Patients::whereDate('created_at', today())->count();

            // Average answers per patient
            $totalAnswers = DB::table('answers')->count();
            $avgAnswersPerPatient = $totalPatients > 0 ? round($totalAnswers / $totalPatients, 1) : 0;

            // Completion rate
            $totalQuestions = Questions::count();
            $completionRate = $totalQuestions > 0 && $totalPatients > 0
                ? round(($totalAnswers / ($totalQuestions * $totalPatients)) * 100, 1)
                : 0;

            // Doctor distribution
            $patientsWithDoctors = Patients::whereNotNull('doctor_id')->count();
            $doctorAssignmentRate = $totalPatients > 0
                ? round(($patientsWithDoctors / $totalPatients) * 100, 1)
                : 0;

            // Most active doctor
            $topDoctor = Patients::select('doctor_id', DB::raw('count(*) as patient_count'))
                ->whereNotNull('doctor_id')
                ->groupBy('doctor_id')
                ->orderByDesc('patient_count')
                ->first();

            $topDoctorName = null;
            $topDoctorCount = 0;
            if ($topDoctor) {
                $doctor = User::find($topDoctor->doctor_id);
                $topDoctorName = $doctor?->name ?? 'Unknown Doctor';
                $topDoctorCount = $topDoctor->patient_count;
            }

            // Trend calculations
            $lastMonthPatients = Patients::whereMonth('created_at', now()->subMonth()->month)
                ->whereYear('created_at', now()->subMonth()->year)
                ->count();

            $monthlyTrend = $lastMonthPatients > 0
                ? round((($newPatientsThisMonth - $lastMonthPatients) / $lastMonthPatients) * 100, 1)
                : 0;

            return compact(
                'totalPatients', 'activePatients', 'hiddenPatients',
                'newPatientsThisMonth', 'newPatientsToday', 'avgAnswersPerPatient',
                'completionRate', 'doctorAssignmentRate', 'topDoctorName',
                'topDoctorCount', 'monthlyTrend'
            );
        });

        return [
            Stat::make('Total Patients', number_format($stats['totalPatients']))
                ->description($stats['activePatients'].' active • '.$stats['hiddenPatients'].' hidden')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([7, 12, 8, 15, 10, 18, $stats['newPatientsThisMonth']])
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20',
                ]),

            Stat::make('New This Month', number_format($stats['newPatientsThisMonth']))
                ->description($stats['newPatientsToday'].' today • '.($stats['monthlyTrend'] >= 0 ? '+' : '').$stats['monthlyTrend'].'% vs last month')
                ->descriptionIcon($stats['monthlyTrend'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($stats['monthlyTrend'] >= 0 ? 'success' : 'warning')
                ->chart([3, 5, 2, 8, 4, 6, $stats['newPatientsThisMonth']])
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20',
                ]),

            Stat::make('Avg. Answers/Patient', number_format($stats['avgAnswersPerPatient'], 1))
                ->description($stats['completionRate'].'% completion rate')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($stats['completionRate'] >= 70 ? 'success' : ($stats['completionRate'] >= 40 ? 'warning' : 'danger'))
                ->chart([45, 52, 48, 61, 58, 63, $stats['completionRate']])
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20',
                ]),

            Stat::make('Doctor Assignment', $stats['doctorAssignmentRate'].'%')
                ->description($stats['topDoctorName'] ? $stats['topDoctorName'].' leads with '.$stats['topDoctorCount'].' patients' : 'No assignments yet')
                ->descriptionIcon('heroicon-m-user-group')
                ->color($stats['doctorAssignmentRate'] >= 80 ? 'success' : ($stats['doctorAssignmentRate'] >= 50 ? 'warning' : 'danger'))
                ->chart([65, 72, 68, 78, 75, 82, $stats['doctorAssignmentRate']])
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20',
                ]),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
