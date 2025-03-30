<?php

namespace App\Filament\Widgets;

use App\Models\Consultation;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ConsultationOverview extends ChartWidget
{
    protected static ?string $heading = 'Consultation Statistics';
    protected static ?string $description = 'Track consultation patterns and completion rates';
    protected static string $color = 'success';
    protected static ?string $pollingInterval = '15s';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        // Get total consultations per day
        $totalData = Consultation::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get completed consultations per day
        $completedData = Consultation::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'complete')
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