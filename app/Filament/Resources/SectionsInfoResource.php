<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SectionsInfoResource\Pages;
use App\Models\SectionsInfo;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SectionsInfoResource extends Resource
{
    protected static ?string $model = SectionsInfo::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Section Information';

    protected static ?string $navigationGroup = '📊 Medical Data';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('sections_info_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('section_name')->label('Section Name')->required(),
                Forms\Components\TextInput::make('section_description')->label('Section Description'),

                Forms\Components\Section::make('AI Settings')
                    ->schema([
                        Forms\Components\Select::make('ai_mode')
                            ->label('AI Mode')
                            ->options([
                                'voice' => 'Voice',
                                'image' => 'Image',
                            ])
                            ->nullable()
                            ->placeholder('None'),

                        Forms\Components\TextInput::make('ai_voice_time')
                            ->label('AI Voice Time (seconds)')
                            ->numeric()
                            ->minValue(1)
                            ->nullable()
                            ->suffix('sec')
                            ->helperText('Recording duration shown to the user in the frontend.'),

                        Forms\Components\RichEditor::make('ai_hint')
                            ->label('AI Hint Content')
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('section_name')
                    ->label('Section Name')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-folder'),

                Tables\Columns\TextColumn::make('section_description')
                    ->label('Description')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable()
                    ->limit(100)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 100) {
                            return $state;
                        }

                        return null;
                    })
                    ->placeholder('No description'),

                Tables\Columns\TextColumn::make('ai_mode')
                    ->label('AI Mode')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'voice' => 'success',
                        'image' => 'info',
                        default => 'gray',
                    })
                    ->placeholder('None')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('ai_voice_time')
                    ->label('Hint Duration')
                    ->suffix(' sec')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Questions')
                    ->counts('questions')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-question-mark-circle')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('M d, Y H:i:s')),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('From'),
                        DatePicker::make('created_until')
                            ->label('Until'),
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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'From '.Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Until '.Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Toggle columns'),
            )
            ->persistFiltersInSession()
            ->deselectAllRecordsWhenFiltered(true)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Section Details')
                    ->modalWidth('3xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        Cache::forget('sections_info_count');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('sections_info_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->emptyStateHeading('No sections yet')
            ->emptyStateDescription('Medical form sections will appear here.')
            ->emptyStateIcon('heroicon-o-folder');
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
            'index' => Pages\ListSectionsInfos::route('/'),
            'create' => Pages\CreateSectionsInfo::route('/create'),
            'view' => Pages\ViewSectionsInfo::route('/{record}'),
            'edit' => Pages\EditSectionsInfo::route('/{record}/edit'),
        ];
    }
}
