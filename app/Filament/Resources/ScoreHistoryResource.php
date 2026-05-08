<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScoreHistoryResource\Pages\CreateScoreHistory;
use App\Filament\Resources\ScoreHistoryResource\Pages\EditScoreHistory;
use App\Filament\Resources\ScoreHistoryResource\Pages\ListScoreHistories;
use App\Models\ScoreHistory;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ScoreHistoryResource extends Resource
{
    protected static ?string $model = ScoreHistory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Score History';

    protected static string|\UnitEnum|null $navigationGroup = '🏥 Patient Management';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('score_history_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('doctor_id')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Doctor Name'),
                TextInput::make('score')->required()->label('Score'),
                TextInput::make('action')->required()->label('Action'),
                DateTimePicker::make('timestamp'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['doctor']))
            ->columns([
                TextColumn::make('id')->toggleable(isToggledHiddenByDefault: false)->searchable(),
                TextColumn::make('doctor.name')->toggleable(isToggledHiddenByDefault: false)->label('Doctor Name')->searchable(),
                TextColumn::make('score')->toggleable(isToggledHiddenByDefault: false)->label('Score'),
                TextColumn::make('action')->toggleable(isToggledHiddenByDefault: false)->label('Action'),
                TextColumn::make('timestamp')->toggleable(isToggledHiddenByDefault: false)->label('Timestamp'),
                TextColumn::make('created_at')->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('updated_at')->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                SelectFilter::make('Doctor Name')
                    ->relationship('doctor', 'name'),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Toggle columns'),
            )
            ->persistFiltersInSession()
            ->deferFilters(false)
            ->deselectAllRecordsWhenFiltered(true)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScoreHistories::route('/'),
            'create' => CreateScoreHistory::route('/create'),
            'edit' => EditScoreHistory::route('/{record}/edit'),
        ];
    }
}
