<?php

namespace App\Filament\Widgets;

use App\Models\PatientHistory;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class PatientChart extends ChartWidget
{
    protected static ?string $heading = 'Patients per Month';

    protected static string $color = 'info';

    protected static ?string $pollingInterval = '3s';

    //public function getDescription(): ?string{return 'The number of blog posts published per month.';}
    //public ?string $filter = 'today';
    protected function getData(): array
    {
        // $activeFilter = $this->filter;

        $data = Trend::model(PatientHistory::class)
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Patients',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /*protected function getFilters(): ?array
    {
    return [
        'today' => 'Today',
        'week' => 'Last week',
        'month' => 'Last month',
        'year' => 'This year',
    ];
    }*/
}
