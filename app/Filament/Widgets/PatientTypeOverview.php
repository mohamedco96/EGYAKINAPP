<?php

namespace App\Filament\Widgets;

use App\Models\Patients;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PatientTypeOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $totalPatients = Patients::count();
        $totalDoctors = User::count();
        $recentPatients = Patients::whereDate('created_at', '>=', now()->subDays(7))->count();
        $recentDoctors = User::whereDate('created_at', '>=', now()->subDays(7))->count();

        return [
            Stat::make('Total Doctors', $totalDoctors)
                ->description($recentDoctors . ' new this week')
                ->descriptionIcon($recentDoctors > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($this->getDoctorTrend())
                ->color($recentDoctors > 0 ? 'success' : 'danger')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => "\$dispatch('openModal', { id: 'doctors-list' })",
                ]),

            Stat::make('Total Patients', $totalPatients)
                ->description($recentPatients . ' new this week')
                ->descriptionIcon($recentPatients > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($this->getPatientTrend())
                ->color($recentPatients > 0 ? 'success' : 'danger')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => "\$dispatch('openModal', { id: 'patients-list' })",
                ]),

            Stat::make('Patient/Doctor Ratio', round($totalPatients / ($totalDoctors ?: 1), 1))
                ->description('Patients per doctor')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
        ];
    }

    protected function getDoctorTrend(): array
    {
        return User::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->pluck('count')
            ->toArray();
    }

    protected function getPatientTrend(): array
    {
        return Patients::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->pluck('count')
            ->toArray();
    }
}
