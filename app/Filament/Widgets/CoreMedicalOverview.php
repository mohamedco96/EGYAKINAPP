<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Patients\Models\Patients;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class CoreMedicalOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Cache expensive queries for 5 minutes
        $stats = Cache::remember('dashboard_core_stats', 300, function () {
            $totalPatients = Patients::count();
            $activePatients = Patients::where('hidden', false)->count();
            $newPatientsToday = Patients::whereDate('created_at', today())->count();
            $newPatientsThisWeek = Patients::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

            $totalConsultations = Consultation::count();
            $pendingConsultations = Consultation::where('status', 'pending')->count();
            $completedToday = Consultation::where('status', 'replied')->whereDate('created_at', today())->count();

            $activeDoctors = User::whereHas('roles', function ($query) {
                $query->where('name', 'doctor');
            })->count();

            // Calculate trends
            $lastWeekPatients = Patients::whereBetween('created_at', [
                now()->subWeeks(2)->startOfWeek(),
                now()->subWeek()->endOfWeek(),
            ])->count();

            $patientTrend = $lastWeekPatients > 0
                ? round((($newPatientsThisWeek - $lastWeekPatients) / $lastWeekPatients) * 100, 1)
                : 0;

            return compact(
                'totalPatients', 'activePatients', 'newPatientsToday', 'newPatientsThisWeek',
                'totalConsultations', 'pendingConsultations', 'completedToday',
                'activeDoctors', 'patientTrend'
            );
        });

        return [
            Stat::make('Total Patients', number_format($stats['totalPatients']))
                ->description($stats['activePatients'].' active patients')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([7, 12, 8, 15, 10, 18, $stats['newPatientsThisWeek']])
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20',
                ]),

            Stat::make('New Patients', $stats['newPatientsToday'])
                ->description('Today • '.$stats['newPatientsThisWeek'].' this week')
                ->descriptionIcon($stats['patientTrend'] >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($stats['patientTrend'] >= 0 ? 'success' : 'warning')
                ->chart([3, 5, 2, 8, 4, 6, $stats['newPatientsToday']])
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20',
                ]),

            Stat::make('Consultations', number_format($stats['totalConsultations']))
                ->description($stats['pendingConsultations'].' pending • '.$stats['completedToday'].' completed today')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($stats['pendingConsultations'] > 10 ? 'warning' : 'success')
                ->chart([5, 8, 12, 6, 15, 9, $stats['completedToday']])
                ->extraAttributes([
                    'class' => 'bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20',
                ]),

            Stat::make('Active Doctors', $stats['activeDoctors'])
                ->description('Medical team members')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
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
