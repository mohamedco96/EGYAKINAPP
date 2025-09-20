<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected function getViewData(): array
    {
        return [
            'actions' => [
                [
                    'label' => 'Add New Patient',
                    'url' => '/admin/patients/create',
                    'icon' => 'heroicon-o-user-plus',
                    'color' => 'primary',
                    'description' => 'Register a new patient',
                ],
                [
                    'label' => 'View Consultations',
                    'url' => '/admin/consultations',
                    'icon' => 'heroicon-o-clipboard-document-list',
                    'color' => 'success',
                    'description' => 'Manage consultations',
                ],
                [
                    'label' => 'Patient Reports',
                    'url' => '/admin/patients',
                    'icon' => 'heroicon-o-document-chart-bar',
                    'color' => 'info',
                    'description' => 'View patient data',
                ],
                [
                    'label' => 'System Settings',
                    'url' => '/admin/settings',
                    'icon' => 'heroicon-o-cog-6-tooth',
                    'color' => 'gray',
                    'description' => 'Configure system',
                ],
            ],
        ];
    }
}
