<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Spatie\Permission\Models\Role;

class RolePermissionChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Roles vs Permissions Distribution';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $roles = Role::withCount('permissions')->get();
        $roleNames = $roles->pluck('name')->map(fn ($name) => ucwords(str_replace(['-', '_'], ' ', $name)))->toArray();
        $permissionCounts = $roles->pluck('permissions_count')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Permissions per Role',
                    'data' => $permissionCounts,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.5)',
                        'rgba(16, 185, 129, 0.5)',
                        'rgba(245, 158, 11, 0.5)',
                        'rgba(239, 68, 68, 0.5)',
                        'rgba(139, 92, 246, 0.5)',
                        'rgba(236, 72, 153, 0.5)',
                    ],
                    'borderColor' => [
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(239, 68, 68, 1)',
                        'rgba(139, 92, 246, 1)',
                        'rgba(236, 72, 153, 1)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $roleNames,
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
            'maintainAspectRatio' => false,
        ];
    }
}
