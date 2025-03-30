<?php

namespace App\Filament\Widgets;

use App\Models\Consultation;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class ConsultationOverview extends ChartWidget
{
    protected static ?string $heading = 'Consultation Statistics';
    protected static ?string $description = 'Track consultation patterns and completion rates';
    protected static string $color = 'success';
    protected static ?string $pollingInterval = '15s';

    protected function getData(): array
    {
        $data = Trend::model(Consultation::class)
            ->between(
                start: now()->startOfMonth(),
                end: now()->endOfMonth(),
            )
            ->perDay()
            ->count();

        $completedData = Trend::model(Consultation::class)
            ->between(
                start: now()->startOfMonth(),
                end: now()->endOfMonth(),
            )
            ->perDay()
            ->query(Consultation::query()->where('status', 'complete'))
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Total Consultations',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Completed Consultations',
                    'data' => $completedData->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
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