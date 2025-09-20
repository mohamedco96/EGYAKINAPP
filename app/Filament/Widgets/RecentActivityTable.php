<?php

namespace App\Filament\Widgets;

use App\Modules\Consultations\Models\Consultation;
use App\Modules\Patients\Models\Patients;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RecentActivityTable extends BaseWidget
{
    protected static ?string $heading = 'Recent Activity';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getRecentActivityQuery())
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'New Patient' => 'success',
                        'Consultation' => 'info',
                        'Completed' => 'primary',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'New Patient' => 'heroicon-m-user-plus',
                        'Consultation' => 'heroicon-m-chat-bubble-left-right',
                        'Completed' => 'heroicon-m-check-circle',
                        default => 'heroicon-m-information-circle',
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('M j, g:i A')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($state) => $state?->format('F j, Y \a\t g:i A')),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->poll('30s')
            ->striped()
            ->emptyStateHeading('No recent activity')
            ->emptyStateDescription('Recent patient registrations and consultations will appear here.')
            ->emptyStateIcon('heroicon-o-clock');
    }

    protected function getRecentActivityQuery(): Builder
    {
        // Get recent patients
        $patients = Patients::select(
            DB::raw("'New Patient' as type"),
            DB::raw("CONCAT('Patient registered: ID #', id) as description"),
            'created_at',
            DB::raw("'patient' as source_type"),
            'id as source_id'
        )
            ->whereDate('created_at', '>=', now()->subDays(7));

        // Get recent consultations
        $consultations = Consultation::select(
            DB::raw("CASE 
                WHEN status = 'replied' THEN 'Completed'
                ELSE 'Consultation'
            END as type"),
            DB::raw("CONCAT('Consultation ', 
                CASE 
                    WHEN status = 'replied' THEN 'completed'
                    WHEN status = 'pending' THEN 'requested'
                    ELSE status
                END
            ) as description"),
            'created_at',
            DB::raw("'consultation' as source_type"),
            'id as source_id'
        )
            ->whereDate('created_at', '>=', now()->subDays(7));

        // Union the queries and return as a builder
        return $patients->union($consultations)
            ->orderBy('created_at', 'desc')
            ->limit(10);
    }
}
