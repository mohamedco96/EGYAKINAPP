<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages\CreatePermission;
use App\Filament\Resources\PermissionResource\Pages\EditPermission;
use App\Filament\Resources\PermissionResource\Pages\ListPermissions;
use App\Filament\Resources\PermissionResource\Pages\ViewPermission;
use App\Models\Permission;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Permissions';

    protected static string|\UnitEnum|null $navigationGroup = '⚙️ Administration';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    protected static int $globalSearchResultsLimit = 20;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('permissions_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $record->name));
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'guard_name'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Permission Information')
                    ->description('Define the permission details and scope')
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Enter permission name (e.g., create-posts, edit-users)')
                            ->helperText('Use lowercase with hyphens. Format: action-resource (e.g., create-posts, edit-users)')
                            ->rules(['regex:/^[a-z0-9-_]+$/', 'min:3'])
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $set, $state) => $set('name', strtolower(str_replace(' ', '-', $state)))),

                        Select::make('guard_name')
                            ->default('web')
                            ->required()
                            ->options([
                                'web' => 'Web Guard (for web routes)',
                                'api' => 'API Guard (for API routes)',
                            ])
                            ->helperText('Select the appropriate guard for this permission'),

                        Select::make('category')
                            ->label('Permission Category')
                            ->options(Permission::getCategories())
                            ->placeholder('Select category')
                            ->required()
                            ->helperText('Categorize this permission for better organization and easier role assignment'),

                        Textarea::make('description')
                            ->placeholder('Optional description of what this permission allows...')
                            ->helperText('Describe what actions this permission grants')
                            ->columnSpanFull()
                            ->rows(2),

                        Placeholder::make('usage_info')
                            ->label('Permission Usage')
                            ->content(fn ($record) => $record ?
                                "Assigned to {$record->roles()->count()} roles | Direct users: {$record->users()->count()} | Created: {$record->created_at?->diffForHumans()}" :
                                'New permission - usage statistics will be available after creation'
                            )
                            ->columnSpanFull()
                            ->visible(fn ($livewire) => $livewire instanceof EditRecord),
                    ])->columns(2),

                Section::make('Assign to Roles')
                    ->description('Select which roles should have this permission')
                    ->schema([
                        CheckboxList::make('roles')
                            ->relationship('roles', 'name')
                            ->columns(3)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->searchable()
                            ->descriptions(
                                Role::all()->mapWithKeys(
                                    fn ($role) => [$role->id => 'Assign this permission to '.ucwords(str_replace(['-', '_'], ' ', $role->name)).' role']
                                )->all()
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Permission Name')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace(['-', '_'], ' ', $state))),

                TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (?string $state): string => $state ? (Permission::getCategories()[$state] ?? ucwords(str_replace(['-', '_'], ' ', $state))) : 'Uncategorized')
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->wrap()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description)
                    ->toggleable(),

                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('secondary')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('roles_count')
                    ->label('Roles')
                    ->counts('roles')
                    ->badge()
                    ->color('success'),

                TextColumn::make('users_count')
                    ->label('Direct Users')
                    ->counts('users')
                    ->badge()
                    ->color('info'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                SelectFilter::make('guard_name')
                    ->label('Guard')
                    ->options([
                        'web' => 'Web Guard',
                        'api' => 'API Guard',
                    ])
                    ->placeholder('All Guards'),

                SelectFilter::make('category')
                    ->label('Category')
                    ->options(Permission::getCategories())
                    ->placeholder('All Categories'),

                Filter::make('has_roles')
                    ->label('Assigned to Roles')
                    ->query(fn (Builder $query): Builder => $query->has('roles'))
                    ->toggle(),

                Filter::make('has_direct_users')
                    ->label('Has Direct Users')
                    ->query(fn (Builder $query): Builder => $query->has('users'))
                    ->toggle(),

                SelectFilter::make('roles')
                    ->label('Assigned to Role')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(5)
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
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info'),
                    EditAction::make()
                        ->color('warning'),
                    Action::make('assign_to_role')
                        ->label('Quick Assign to Role')
                        ->icon('heroicon-o-shield-check')
                        ->color('success')
                        ->schema([
                            CheckboxList::make('roles')
                                ->label('Select Roles')
                                ->options(Role::all()->pluck('name', 'id'))
                                ->columns(2)
                                ->searchable(),
                        ])
                        ->action(function (Permission $record, array $data) {
                            $record->roles()->sync($data['roles'] ?? []);
                            Notification::make()
                                ->title('Permission assigned to roles successfully')
                                ->success()
                                ->send();
                        }),
                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Permission')
                        ->modalDescription('This will remove this permission from all roles and users. This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete it')
                        ->color('danger'),
                ])->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Permissions')
                        ->modalDescription('Are you sure you want to delete the selected permissions? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete them'),
                ]),
            ])
            ->emptyStateHeading('No permissions found')
            ->emptyStateDescription('Create your first permission to get started with permission management.')
            ->emptyStateIcon('heroicon-o-key');
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
            'index' => ListPermissions::route('/'),
            'create' => CreatePermission::route('/create'),
            'view' => ViewPermission::route('/{record}'),
            'edit' => EditPermission::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Permissions';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🔐 Access Control';
    }

    public static function getModelLabel(): string
    {
        return 'Permission';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Permissions';
    }
}
