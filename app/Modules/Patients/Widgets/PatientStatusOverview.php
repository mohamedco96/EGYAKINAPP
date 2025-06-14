<?php

namespace App\Modules\Patients\Widgets;

use App\Modules\Patients\Models\PatientStatus;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PatientStatusOverview extends ChartWidget
{
    protected static ?string $heading = 'Patient Status Distribution';
    protected static ?string $description = 'Overview of patient statuses across the system';
    protected static string $color = 'warning';
    protected static ?string $pollingInterval = '15s';

    protected function getData(): array
    {
        $statuses = PatientStatus::select('key', DB::raw('count(*) as count'))
            ->groupBy('key')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Patients by Status',
                    'data' => $statuses->pluck('count')->toArray(),
                    'backgroundColor' => [
                        '#3b82f6', // blue
                        '#22c55e', // green
                        '#ef4444', // red
                        '#f59e0b', // yellow
                        '#8b5cf6', // purple
                    ],
                ],
            ],
            'labels' => $statuses->pluck('key')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
} 