<?php

namespace App\Filament\Widgets;

use App\Modules\Consultations\Models\Consultation;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ConsultationTrendsChart extends ChartWidget
{
    protected static ?string $heading = 'Consultation Trends';

    protected static ?string $description = 'Daily consultation activity over the past 30 days';

    protected static string $color = 'info';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        return Cache::remember('consultation_trends_chart', 300, function () {
            $startDate = now()->subDays(29)->startOfDay();
            $endDate = now()->endOfDay();

            // Get consultations per day for the last 30 days
            $consultationsData = Consultation::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "replied" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending')
            )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            // Generate all dates in range
            $dates = [];
            $totalData = [];
            $completedData = [];
            $pendingData = [];

            for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
                $dateStr = $date->format('Y-m-d');
                $dates[] = $date->format('M j');

                $dayData = $consultationsData->get($dateStr);
                $totalData[] = $dayData ? $dayData->total : 0;
                $completedData[] = $dayData ? $dayData->completed : 0;
                $pendingData[] = $dayData ? $dayData->pending : 0;
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Total Consultations',
                        'data' => $totalData,
                        'borderColor' => '#3b82f6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'fill' => true,
                        'tension' => 0.4,
                    ],
                    [
                        'label' => 'Completed',
                        'data' => $completedData,
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'fill' => false,
                        'tension' => 0.4,
                    ],
                    [
                        'label' => 'Pending',
                        'data' => $pendingData,
                        'borderColor' => '#f59e0b',
                        'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                        'fill' => false,
                        'tension' => 0.4,
                    ],
                ],
                'labels' => $dates,
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
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'display' => true,
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }
}
