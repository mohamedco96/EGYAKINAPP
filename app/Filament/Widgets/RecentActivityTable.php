<?php

namespace App\Filament\Widgets;

use App\Modules\Patients\Models\Patients;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class RecentActivityTable extends BaseWidget
{
    protected static ?string $heading = 'Recent Patient Registrations';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getRecentPatientsQuery())
            ->columns([
                TextColumn::make('id')
                    ->label('Patient ID')
                    ->badge()
                    ->color('primary')
                    ->prefix('#'),

                TextColumn::make('doctor.name')
                    ->label('Assigned Doctor')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 30 ? $state : null;
                    }),

                IconColumn::make('hidden')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye-slash')
                    ->falseIcon('heroicon-o-eye')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn ($state) => $state ? 'Hidden' : 'Active'),

                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('M j, g:i A')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($state) => $state?->format('F j, Y \a\t g:i A')),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Patients $record): string => "/admin/patients/{$record->id}/edit"),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->poll('30s')
            ->striped()
            ->emptyStateHeading('No recent patients')
            ->emptyStateDescription('Recent patient registrations will appear here.')
            ->emptyStateIcon('heroicon-o-users');
    }

    protected function getRecentPatientsQuery(): Builder
    {
        $ids = Cache::remember('widget_recent_patient_registrations', 60, fn () => Patients::query()
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->latest()
            ->limit(10)
            ->pluck('id')
            ->toArray()
        );

        return Patients::query()->with('doctor')->whereIn('id', $ids)->latest();
    }
}
