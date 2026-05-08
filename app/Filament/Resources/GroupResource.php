<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GroupResource\Pages\CreateGroup;
use App\Filament\Resources\GroupResource\Pages\EditGroup;
use App\Filament\Resources\GroupResource\Pages\ListGroups;
use App\Filament\Resources\GroupResource\Pages\ViewGroup;
use App\Models\Group;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Groups';

    protected static string|\UnitEnum|null $navigationGroup = '📱 Community';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('groups_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Group Information')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Group Name')
                    ->helperText('The name of the group'),
                Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Group Owner')
                    ->getSearchResultsUsing(fn (string $search) => User::where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('lname', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })->limit(50)->get()->pluck('full_name_with_email', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->full_name_with_email),
                Select::make('privacy')
                    ->options([
                        'public' => 'Public',
                        'private' => 'Private',
                    ])
                    ->default('public')
                    ->required()
                    ->native(false)
                    ->label('Privacy Setting')
                    ->helperText('Public groups are visible to all, private groups require approval'),
                Textarea::make('description')
                    ->nullable()
                    ->rows(3)
                    ->columnSpanFull()
                    ->label('Group Description'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['owner']))
            ->columns([
                TextColumn::make('id')->label('ID')->badge()->color('gray')->searchable()->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),
                TextColumn::make('privacy')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'public' => 'success',
                        'private' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'public' => 'heroicon-o-globe-alt',
                        'private' => 'heroicon-o-lock-closed',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->formatStateUsing(fn ($record) => $record->owner ? $record->owner->name.' '.($record->owner->lname ?? '') : 'N/A')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable()
                    ->limit(50)
                    ->wrap()
                    ->placeholder('No description')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')->label('Created')->dateTime()->sortable()->since()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                SelectFilter::make('privacy')
                    ->options([
                        'public' => 'Public',
                        'private' => 'Private',
                    ]),
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
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()->after(fn () => Cache::forget('groups_count')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->after(fn () => Cache::forget('groups_count')),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No groups yet')
            ->emptyStateDescription('Community groups will appear here.')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGroups::route('/'),
            'create' => CreateGroup::route('/create'),
            'view' => ViewGroup::route('/{record}'),
            'edit' => EditGroup::route('/{record}/edit'),
        ];
    }
}
