<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ConsultationTrendsChart;
use App\Filament\Widgets\CoreMedicalOverview;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\RecentActivityTable;
use App\Filament\Widgets\RecentConsultationsTable;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = -1;

    protected static string $view = 'filament.pages.dashboard';

    public function getWidgets(): array
    {
        return [
            CoreMedicalOverview::class,
            ConsultationTrendsChart::class,
            RecentActivityTable::class,
            RecentConsultationsTable::class,
            QuickActionsWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 4,
        ];
    }

    public function getTitle(): string
    {
        return 'Medical Dashboard';
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }
}
