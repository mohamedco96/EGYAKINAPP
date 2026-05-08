<?php

namespace App\Filament\Widgets;

use App\Modules\Consultations\Models\Consultation;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ConsultationOverview extends ChartWidget
{
    protected ?string $heading = 'Monthly Consultation Overview';

    protected ?string $description = 'Consultation patterns for current month';

    protected string $color = 'success';

    protected ?string $pollingInterval = null;

    protected static ?int $sort = 10;

    protected function getData(): array
    {
        return Cache::remember('consultation_overview_chart_'.now()->format('Y-m'), 300, function () {
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();

            $totalData = Consultation::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $completedData = Consultation::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'replied')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return [
                'datasets' => [
                    [
                        'label' => 'Total Consultations',
                        'data' => $totalData->pluck('count')->toArray(),
                        'borderColor' => '#3b82f6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'fill' => true,
                    ],
                    [
                        'label' => 'Completed Consultations',
                        'data' => $completedData->pluck('count')->toArray(),
                        'borderColor' => '#22c55e',
                        'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                        'fill' => true,
                    ],
                ],
                'labels' => $totalData->pluck('date')->toArray(),
            ];
        });
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
