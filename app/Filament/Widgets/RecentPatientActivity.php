<?php

namespace App\Filament\Widgets;

use App\Modules\Patients\Models\Patients;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class RecentPatientActivity extends BaseWidget
{
    protected static ?int $sort = 12;

    protected static ?string $heading = 'Recent Patient Registrations';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getRecentActivityQuery())
            ->columns([
                TextColumn::make('id')
                    ->label('Patient ID')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('doctor.name')
                    ->label('Assigned Doctor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('M j, g:i A')
                    ->since()
                    ->sortable(),
                IconColumn::make('hidden')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye-slash')
                    ->falseIcon('heroicon-o-eye')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn ($state) => $state ? 'Hidden' : 'Active')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('30s')
            ->emptyStateHeading('No recent patients')
            ->emptyStateDescription('Recent patient registrations will appear here.')
            ->emptyStateIcon('heroicon-o-users');
    }

    protected function getRecentActivityQuery(): Builder
    {
        $ids = Cache::remember('widget_recent_patient_activity', 60, fn () => Patients::query()
            ->latest()
            ->limit(5)
            ->pluck('id')
            ->toArray()
        );

        return Patients::query()->with('doctor')->whereIn('id', $ids)->latest();
    }
}
