<?php

namespace App\Filament\Widgets;

use App\Models\ConsultationDoctor;
use App\Models\Consultation;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class DoctorPerformanceOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $topDoctors = ConsultationDoctor::select('consult_doctor_id', DB::raw('count(*) as consultation_count'))
            ->groupBy('consult_doctor_id')
            ->orderByDesc('consultation_count')
            ->limit(5)
            ->get();

        $totalConsultations = ConsultationDoctor::count();
        $completedConsultations = ConsultationDoctor::whereHas('consultation', function ($query) {
            $query->where('status', 'replied');
        })->count();

        $completionRate = $totalConsultations > 0 
            ? round(($completedConsultations / $totalConsultations) * 100, 1) 
            : 0;

        return [
            Stat::make('Total Consultations', $totalConsultations)
                ->description($completedConsultations . ' completed')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('success'),

            Stat::make('Completion Rate', $completionRate . '%')
                ->description('Of all consultations')
                ->descriptionIcon($completionRate >= 80 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($completionRate >= 80 ? 'success' : 'warning'),

            Stat::make('Top Doctor', $topDoctors->first() ? User::find($topDoctors->first()->doctor_id)?->name : 'N/A')
                ->description($topDoctors->first()?->consultation_count . ' consultations')
                ->descriptionIcon('heroicon-m-star')
                ->color('info'),
        ];
    }
} 