<?php

namespace App\Filament\Widgets;

use App\Modules\Patients\Models\Patients;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

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
                Tables\Columns\TextColumn::make('id')
                    ->label('Patient ID')
                    ->badge()
                    ->color('primary')
                    ->prefix('#'),

                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Assigned Doctor')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\IconColumn::make('hidden')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye-slash')
                    ->falseIcon('heroicon-o-eye')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn ($state) => $state ? 'Hidden' : 'Active'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('M j, g:i A')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($state) => $state?->format('F j, Y \a\t g:i A')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
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
        return Patients::query()
            ->with('doctor')
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->latest()
            ->limit(10);
    }
}
