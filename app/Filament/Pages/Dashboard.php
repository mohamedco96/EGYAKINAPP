<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ConsultationTrendsChart;
use App\Filament\Widgets\CoreMedicalOverview;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\RecentActivityTable;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    public function getWidgets(): array
    {
        return [
            CoreMedicalOverview::class,
            ConsultationTrendsChart::class,
            RecentActivityTable::class,
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
        $greeting = $this->getGreeting();
        $user = auth()->user();

        return "{$greeting}, ".($user->name ?? 'Admin').'!';
    }

    public function getSubheading(): ?string
    {
        return 'Welcome to your medical practice dashboard. Here\'s an overview of your key metrics and recent activity.';
    }

    private function getGreeting(): string
    {
        $hour = now()->hour;

        if ($hour < 12) {
            return 'Good morning';
        } elseif ($hour < 17) {
            return 'Good afternoon';
        } else {
            return 'Good evening';
        }
    }
}
