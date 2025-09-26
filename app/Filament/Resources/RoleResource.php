<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = '👨‍⚕️ User Management';

    protected static ?string $navigationLabel = 'Roles & Permissions';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    protected static int $globalSearchResultsLimit = 20;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $record->name));
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'guard_name'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Role Information')
                    ->description('Define the basic role details')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Enter role name (e.g., admin, editor, user)')
                            ->helperText('Use lowercase with hyphens for consistency')
                            ->rules(['regex:/^[a-z0-9-_]+$/', 'min:2'])
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $set, $state) => $set('name', strtolower(str_replace(' ', '-', $state)))),

                        TextInput::make('guard_name')
                            ->default('web')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('web')
                            ->helperText('Authentication guard (web for web routes, api for API routes)')
                            ->datalist(['web', 'api']),

                        Textarea::make('description')
                            ->placeholder('Optional description of this role...')
                            ->helperText('Describe what this role is used for')
                            ->columnSpanFull()
                            ->rows(2),

                        Placeholder::make('created_info')
                            ->label('Role Statistics')
                            ->content(fn ($record) => $record ?
                                "Created: {$record->created_at?->diffForHumans()} | Users: {$record->users()->count()} | Permissions: {$record->permissions()->count()}" :
                                'New role - statistics will be available after creation'
                            )
                            ->columnSpanFull()
                            ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord),
                    ])->columns(2),

                Section::make('Permissions Assignment')
                    ->description('Select which permissions this role should have')
                    ->icon('heroicon-o-key')
                    ->schema([
                        CheckboxList::make('permissions')
                            ->relationship('permissions', 'name')
                            ->columns(4)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(fn (Permission $record): string => ucwords(str_replace(['-', '_'], ' ', $record->name)))
                            ->descriptions(
                                fn (): array => Permission::all()->pluck('name', 'id')->map(
                                    fn ($name) => 'Grant access to '.str_replace(['-', '_'], ' ', $name).' functionality'
                                )->toArray()
                            )
                            ->hint('Select all permissions that users with this role should have')
                            ->hintColor('primary'),
                    ])->collapsible(),
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
                    ->label('Role Name')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('secondary'),

                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('success'),

                TextColumn::make('users_count')
                    ->label('Users')
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
            ->filters([
                Tables\Filters\SelectFilter::make('guard_name')
                    ->label('Guard')
                    ->options([
                        'web' => 'Web Guard',
                        'api' => 'API Guard',
                    ])
                    ->placeholder('All Guards'),

                Filter::make('has_permissions')
                    ->label('Has Permissions')
                    ->query(fn (Builder $query): Builder => $query->has('permissions'))
                    ->toggle(),

                Filter::make('has_users')
                    ->label('Has Users')
                    ->query(fn (Builder $query): Builder => $query->has('users'))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('permissions')
                    ->label('With Permission')
                    ->relationship('permissions', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info'),
                    EditAction::make()
                        ->color('warning'),
                    Action::make('assign_users')
                        ->label('Assign Users')
                        ->icon('heroicon-o-users')
                        ->color('success')
                        ->form([
                            CheckboxList::make('users')
                                ->label('Select Users')
                                ->options(User::all()->pluck('name', 'id'))
                                ->columns(2)
                                ->searchable(),
                        ])
                        ->action(function (Role $record, array $data) {
                            $record->users()->sync($data['users'] ?? []);
                            \Filament\Notifications\Notification::make()
                                ->title('Users assigned successfully')
                                ->success()
                                ->send();
                        }),
                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Role')
                        ->modalDescription('Are you sure you want to delete this role? This will remove it from all users.')
                        ->modalSubmitActionLabel('Yes, delete it')
                        ->color('danger'),
                ])->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Roles')
                        ->modalDescription('Are you sure you want to delete the selected roles? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete them'),
                ]),
            ])
            ->emptyStateHeading('No roles found')
            ->emptyStateDescription('Create your first role to get started with role-based access control.')
            ->emptyStateIcon('heroicon-o-shield-check');
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Roles';
    }

    public static function getNavigationGroup(): ?string
    {
        return '🔐 Access Control';
    }

    public static function getModelLabel(): string
    {
        return 'Role';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Roles';
    }
}
