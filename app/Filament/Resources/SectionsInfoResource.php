<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SectionsInfoResource\Pages\CreateSectionsInfo;
use App\Filament\Resources\SectionsInfoResource\Pages\EditSectionsInfo;
use App\Filament\Resources\SectionsInfoResource\Pages\ListSectionsInfos;
use App\Filament\Resources\SectionsInfoResource\Pages\ViewSectionsInfo;
use App\Models\SectionsInfo;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SectionsInfoResource extends Resource
{
    protected static ?string $model = SectionsInfo::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Questionnaire Sections';

    protected static string|\UnitEnum|null $navigationGroup = '📊 Medical Data';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('sections_info_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('section_name')->label('Section Name')->required(),
                TextInput::make('section_description')->label('Section Description'),

                Section::make('AI Settings')
                    ->schema([
                        Select::make('ai_mode')
                            ->label('AI Mode')
                            ->options([
                                'voice' => 'Voice',
                                'image' => 'Image',
                            ])
                            ->nullable()
                            ->placeholder('None'),

                        TextInput::make('ai_voice_time')
                            ->label('AI Voice Time (seconds)')
                            ->numeric()
                            ->minValue(1)
                            ->nullable()
                            ->suffix('sec')
                            ->helperText('Recording duration shown to the user in the frontend.'),

                        RichEditor::make('ai_hint')
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
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('section_name')
                    ->label('Section Name')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-folder'),

                TextColumn::make('section_description')
                    ->label('Description')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable()
                    ->limit(100)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 100) {
                            return $state;
                        }

                        return null;
                    })
                    ->placeholder('No description'),

                TextColumn::make('ai_mode')
                    ->label('AI Mode')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'voice' => 'success',
                        'image' => 'info',
                        default => 'gray',
                    })
                    ->placeholder('None')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('ai_voice_time')
                    ->label('Hint Duration')
                    ->suffix(' sec')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('questions_count')
                    ->label('Questions')
                    ->counts('questions')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-question-mark-circle')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s')),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('M d, Y H:i:s')),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                SelectFilter::make('ai_mode')
                    ->label('AI Mode')
                    ->options([
                        'voice' => 'Voice',
                        'image' => 'Image',
                    ])
                    ->placeholder('All modes'),

                Filter::make('created_at')
                    ->schema([
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
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
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
                ViewAction::make()
                    ->modalHeading('Section Details')
                    ->modalWidth('3xl'),
                EditAction::make(),
                DeleteAction::make()
                    ->after(function () {
                        Cache::forget('sections_info_count');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('sections_info_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
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
            'index' => ListSectionsInfos::route('/'),
            'create' => CreateSectionsInfo::route('/create'),
            'view' => ViewSectionsInfo::route('/{record}'),
            'edit' => EditSectionsInfo::route('/{record}/edit'),
        ];
    }
}
