<?php

namespace App\Filament\Widgets;

use App\Modules\Consultations\Models\Consultation;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

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
                TextColumn::make('id')
                    ->label('Consultation ID')
                    ->badge()
                    ->color('info')
                    ->prefix('#'),

                TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->searchable()
                    ->limit(25),

                TextColumn::make('consult_message')
                    ->label('Message')
                    ->limit(40)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 40 ? $state : null;
                    }),

                TextColumn::make('status')
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

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, g:i A')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($state) => $state?->format('F j, Y \a\t g:i A')),
            ])
            ->recordActions([
                ViewAction::make()
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
        $ids = Cache::remember('widget_recent_consultations', 60, fn () => Consultation::query()
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->latest()
            ->limit(10)
            ->pluck('id')
            ->toArray()
        );

        return Consultation::query()->with(['doctor', 'patient'])->whereIn('id', $ids)->latest();
    }
}
