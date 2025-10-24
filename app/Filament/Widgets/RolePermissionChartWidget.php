<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Role & Permission Overview';

    protected static ?string $description = 'Distribution of roles and permissions across the system';

    protected static string $color = 'primary';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        return Cache::remember('role_permission_chart', 300, function () {
            // Get role distribution
            $roleData = User::select(
                'roles.name as role_name',
                DB::raw('COUNT(users.id) as user_count')
            )
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_type', User::class)
                ->groupBy('roles.name')
                ->orderBy('user_count', 'desc')
                ->get();

            // Get permission distribution by category
            $permissionData = Permission::select(
                'category',
                DB::raw('COUNT(*) as permission_count')
            )
                ->whereNotNull('category')
                ->groupBy('category')
                ->orderBy('permission_count', 'desc')
                ->get();

            // Prepare role chart data
            $roleLabels = $roleData->pluck('role_name')->toArray();
            $roleCounts = $roleData->pluck('user_count')->toArray();

            // Prepare permission chart data
            $permissionLabels = $permissionData->pluck('category')->toArray();
            $permissionCounts = $permissionData->pluck('permission_count')->toArray();

            return [
                'datasets' => [
                    [
                        'label' => 'Users per Role',
                        'data' => $roleCounts,
                        'backgroundColor' => [
                            '#3b82f6', // Blue
                            '#10b981', // Green
                            '#f59e0b', // Yellow
                            '#ef4444', // Red
                            '#8b5cf6', // Purple
                            '#06b6d4', // Cyan
                            '#84cc16', // Lime
                            '#f97316', // Orange
                        ],
                        'borderColor' => '#ffffff',
                        'borderWidth' => 2,
                    ],
                ],
                'labels' => $roleLabels,
                'permissionData' => [
                    'labels' => $permissionLabels,
                    'counts' => $permissionCounts,
                ],
            ];
        });
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        $data = $this->getData();

        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
                'tooltip' => [
                    'enabled' => true,
                    'callbacks' => [
                        'label' => 'function(context) {
                            const label = context.label || "";
                            const value = context.parsed || 0;
                            return label + ": " + value + " users";
                        }',
                    ],
                ],
                'title' => [
                    'display' => true,
                    'text' => 'User Distribution by Role',
                    'font' => [
                        'size' => 16,
                        'weight' => 'bold',
                    ],
                ],
            ],
            'elements' => [
                'arc' => [
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff',
                ],
            ],
            'cutout' => '50%',
        ];
    }

    protected function getFooter(): ?string
    {
        $data = $this->getData();
        $permissionData = $data['permissionData'];

        if (empty($permissionData['labels'])) {
            return null;
        }

        $topCategories = array_slice($permissionData['labels'], 0, 3);
        $topCounts = array_slice($permissionData['counts'], 0, 3);

        $footer = 'Top Permission Categories: ';
        $categoryTexts = [];

        for ($i = 0; $i < count($topCategories); $i++) {
            $categoryTexts[] = ucfirst($topCategories[$i]).' ('.$topCounts[$i].')';
        }

        return $footer.implode(', ', $categoryTexts);
    }

    protected function getExtraFooterActions(): array
    {
        return [
            \Filament\Actions\Action::make('view_permissions')
                ->label('View All Permissions')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->url(fn () => route('filament.admin.resources.permissions.index'))
                ->openUrlInNewTab(false),

            \Filament\Actions\Action::make('view_roles')
                ->label('View All Roles')
                ->icon('heroicon-o-shield-check')
                ->color('gray')
                ->url(fn () => route('filament.admin.resources.roles.index'))
                ->openUrlInNewTab(false),
        ];
    }
}
