<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DoseResource\Pages;
use App\Modules\Doses\Models\Dose;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class DoseResource extends Resource
{
    protected static ?string $model = Dose::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Dose Modifiers';

    protected static ?string $navigationGroup = '📊 Medical Data';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Description' => $record->description ? strip_tags(str($record->description)->limit(100)) : 'No description',
            'Created' => $record->created_at->format('M j, Y'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Dose Information')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                Section::make('Dose Details')
                                    ->description('Enter the basic information for this dose modifier')
                                    ->icon('heroicon-m-beaker')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('title')
                                                    ->label('Dose Title')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->live(onBlur: true)
                                                    ->placeholder('Enter a descriptive title')
                                                    ->helperText('A clear, concise title for this dose modifier')
                                                    ->columnSpan(2),
                                            ]),
                                    ])
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),

                        Tabs\Tab::make('Description')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                Section::make('Detailed Description')
                                    ->description('Provide comprehensive information about this dose modifier')
                                    ->icon('heroicon-m-document-text')
                                    ->schema([
                                        Forms\Components\RichEditor::make('description')
                                            ->label('Description')
                                            ->placeholder('Enter detailed description...')
                                            ->toolbarButtons([
                                                'blockquote',
                                                'bold',
                                                'bulletList',
                                                'codeBlock',
                                                'h2',
                                                'h3',
                                                'italic',
                                                'link',
                                                'orderedList',
                                                'redo',
                                                'strike',
                                                'table',
                                                'underline',
                                                'undo',
                                            ])
                                            ->columnSpanFull()
                                            ->helperText('Provide context, indications, or general information'),
                                    ])
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),

                        Tabs\Tab::make('Dosage Information')
                            ->icon('heroicon-m-calculator')
                            ->schema([
                                Section::make('Dose Specifications')
                                    ->description('Enter specific dosage information and calculations')
                                    ->icon('heroicon-m-calculator')
                                    ->schema([
                                        Forms\Components\RichEditor::make('dose')
                                            ->label('Dosage Details')
                                            ->required()
                                            ->placeholder('Enter specific dosage information...')
                                            ->toolbarButtons([
                                                'blockquote',
                                                'bold',
                                                'bulletList',
                                                'codeBlock',
                                                'h2',
                                                'h3',
                                                'italic',
                                                'link',
                                                'orderedList',
                                                'redo',
                                                'strike',
                                                'table',
                                                'underline',
                                                'undo',
                                            ])
                                            ->columnSpanFull()
                                            ->helperText('Include dosage calculations, modifications, and specific instructions'),
                                    ])
                                    ->collapsible()
                                    ->persistCollapsed(),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Stack::make([
                        Tables\Columns\TextColumn::make('title')
                            ->label('Dose Title')
                            ->searchable()
                            ->sortable()
                            ->weight(FontWeight::Bold)
                            ->color('primary')
                            ->size('lg')
                            ->icon('heroicon-m-beaker')
                            ->copyable()
                            ->tooltip('Click to copy')
                            ->limit(50),

                        Tables\Columns\TextColumn::make('description')
                            ->label('Description')
                            ->html()
                            ->searchable()
                            ->limit(100)
                            ->tooltip(fn ($record) => strip_tags($record->description))
                            ->placeholder('No description provided')
                            ->color('gray')
                            ->size('sm'),
                    ])->space(1),

                    Stack::make([
                        Tables\Columns\TextColumn::make('dose')
                            ->label('Dosage Information')
                            ->html()
                            ->limit(80)
                            ->tooltip(fn ($record) => strip_tags($record->dose))
                            ->badge()
                            ->color('success')
                            ->icon('heroicon-m-calculator'),

                        Tables\Columns\TextColumn::make('created_at')
                            ->label('Created')
                            ->dateTime('M j, Y g:i A')
                            ->sortable()
                            ->since()
                            ->tooltip(fn ($record) => $record->created_at->format('F j, Y \\a\\t g:i A'))
                            ->color('gray')
                            ->size('sm')
                            ->icon('heroicon-m-clock'),
                    ])->space(1)
                        ->alignment('end'),
                ])->from('md'),

                // Mobile layout
                Stack::make([
                    Tables\Columns\TextColumn::make('title')
                        ->searchable()
                        ->sortable()
                        ->weight(FontWeight::Bold)
                        ->color('primary')
                        ->icon('heroicon-m-beaker')
                        ->limit(30),

                    Tables\Columns\TextColumn::make('description')
                        ->html()
                        ->limit(60)
                        ->color('gray')
                        ->size('sm')
                        ->placeholder('No description'),

                    Tables\Columns\TextColumn::make('created_at')
                        ->since()
                        ->color('gray')
                        ->size('xs')
                        ->icon('heroicon-m-clock'),
                ])->space(1)
                    ->visibleFrom('md', inverted: true),
            ])
            ->contentGrid([
                'md' => 1,
                'lg' => 1,
            ])
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                Filter::make('created_at')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('created_from')
                                    ->label('Created From')
                                    ->placeholder('Select start date')
                                    ->native(false)
                                    ->closeOnDateSelection(),
                                DatePicker::make('created_until')
                                    ->label('Created Until')
                                    ->placeholder('Select end date')
                                    ->native(false)
                                    ->closeOnDateSelection(),
                            ]),
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
                            $indicators['created_from'] = 'Created from '.\Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Created until '.\Carbon\Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                SelectFilter::make('has_description')
                    ->label('Content Status')
                    ->options([
                        '1' => 'Has Description',
                        '0' => 'No Description',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === '1') {
                            return $query->whereNotNull('description')
                                ->where('description', '!=', '');
                        } elseif ($data['value'] === '0') {
                            return $query->where(function ($query) {
                                $query->whereNull('description')
                                    ->orWhere('description', '');
                            });
                        }

                        return $query;
                    }),

                Filter::make('recent')
                    ->label('Recent Additions')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30)))
                    ->toggle(),

                Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->startOfWeek()))
                    ->toggle(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Columns')
                    ->icon('heroicon-m-view-columns')
                    ->color('gray'),
            )
            ->persistFiltersInSession()
            ->deselectAllRecordsWhenFiltered(true)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filters')
                    ->icon('heroicon-m-funnel')
                    ->color('gray')
                    ->size('sm'),
            )
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info')
                        ->icon('heroicon-m-eye'),
                    Tables\Actions\EditAction::make()
                        ->color('warning')
                        ->icon('heroicon-m-pencil-square'),
                    Tables\Actions\ReplicateAction::make()
                        ->color('success')
                        ->icon('heroicon-m-square-2-stack')
                        ->form([
                            Forms\Components\TextInput::make('title')
                                ->label('New Title')
                                ->required()
                                ->default(fn ($record) => $record->title.' (Copy)'),
                        ])
                        ->beforeReplicaSaved(function (array $data, $record): void {
                            $data['title'] = $data['title'] ?? $record->title.' (Copy)';
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->color('danger')
                        ->icon('heroicon-m-trash'),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-m-trash'),
                    ExportBulkAction::make()
                        ->icon('heroicon-m-arrow-down-tray'),
                    Tables\Actions\BulkAction::make('mark_reviewed')
                        ->label('Mark as Reviewed')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            // Add your custom bulk action logic here
                            \Filament\Notifications\Notification::make()
                                ->title('Doses marked as reviewed')
                                ->success()
                                ->send();
                        }),
                ])
                    ->label('Bulk Actions'),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-m-plus')
                    ->label('Create First Dose Modifier'),
            ])
            ->emptyStateHeading('No dose modifiers yet')
            ->emptyStateDescription('Create your first dose modifier to get started with medication dosing guidelines.')
            ->emptyStateIcon('heroicon-o-beaker')
            ->recordUrl(null)
            ->recordAction(Tables\Actions\ViewAction::class)
            ->searchPlaceholder('Search doses by title or description...')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
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
            'index' => Pages\ListDoses::route('/'),
            'create' => Pages\CreateDose::route('/create'),
            'view' => Pages\ViewDose::route('/{record}'),
            'edit' => Pages\EditDose::route('/{record}/edit'),
        ];
    }
}
