<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Modules\Consultations\Models\ConsultationDoctor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DoctorPerformanceOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 11;

    protected function getStats(): array
    {
        $data = Cache::remember('doctor_performance_stats', 300, function () {
            $topDoctors = ConsultationDoctor::select('consult_doctor_id', DB::raw('count(*) as consultation_count'))
                ->groupBy('consult_doctor_id')
                ->orderByDesc('consultation_count')
                ->limit(5)
                ->get();

            $totalConsultations = ConsultationDoctor::count();
            $completedConsultations = ConsultationDoctor::whereHas('consultation', function ($query) {
                $query->where('status', 'replied');
            })->count();

            $topDoctorName = $topDoctors->first()
                ? User::find($topDoctors->first()->consult_doctor_id)?->name
                : null;

            return [
                'totalConsultations' => $totalConsultations,
                'completedConsultations' => $completedConsultations,
                'topDoctorName' => $topDoctorName,
                'topDoctorCount' => $topDoctors->first()?->consultation_count ?? 0,
            ];
        });

        $completionRate = $data['totalConsultations'] > 0
            ? round(($data['completedConsultations'] / $data['totalConsultations']) * 100, 1)
            : 0;

        return [
            Stat::make('Total Consultations', $data['totalConsultations'])
                ->description($data['completedConsultations'].' completed')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('success'),

            Stat::make('Completion Rate', $completionRate.'%')
                ->description('Of all consultations')
                ->descriptionIcon($completionRate >= 80 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($completionRate >= 80 ? 'success' : 'warning'),

            Stat::make('Top Doctor', $data['topDoctorName'] ?? 'N/A')
                ->description($data['topDoctorCount'].' consultations')
                ->descriptionIcon('heroicon-m-star')
                ->color('info'),
        ];
    }
}
