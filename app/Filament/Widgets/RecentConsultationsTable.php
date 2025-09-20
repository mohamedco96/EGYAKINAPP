<?php

namespace App\Filament\Widgets;

use App\Modules\Consultations\Models\Consultation;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentConsultationsTable extends BaseWidget
{
    protected static ?string $heading = 'Recent Consultations';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getRecentConsultationsQuery())
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Consultation ID')
                    ->badge()
                    ->color('info')
                    ->prefix('#'),

                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('consult_message')
                    ->label('Message')
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 40 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'replied' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-m-clock',
                        'replied' => 'heroicon-m-check-circle',
                        'closed' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, g:i A')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($state) => $state?->format('F j, Y \a\t g:i A')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Consultation $record): string => "/admin/consultations/{$record->id}"),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->poll('30s')
            ->striped()
            ->emptyStateHeading('No recent consultations')
            ->emptyStateDescription('Recent consultation requests will appear here.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
    }

    protected function getRecentConsultationsQuery(): Builder
    {
        return Consultation::query()
            ->with(['doctor', 'patient'])
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->latest()
            ->limit(10);
    }
}
