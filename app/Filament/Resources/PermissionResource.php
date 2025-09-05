<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Permission Information')
                    ->description('Manage permission details')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Enter permission name')
                            ->helperText('The name of the permission (e.g., create-posts, edit-users, view-reports)')
                            ->rules(['regex:/^[a-z0-9-_]+$/'])
                            ->validationMessages([
                                'regex' => 'Permission name must contain only lowercase letters, numbers, hyphens, and underscores.',
                            ]),

                        TextInput::make('guard_name')
                            ->default('web')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('web')
                            ->helperText('Guard name for the permission (usually "web")'),
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
                            ->getOptionLabelFromRecordUsing(fn (Role $record): string => ucwords(str_replace(['-', '_'], ' ', $record->name)))
                            ->descriptions(
                                fn (): array => Role::all()->pluck('name', 'id')->map(
                                    fn ($name) => 'Assign this permission to '.ucwords(str_replace(['-', '_'], ' ', $name)).' role'
                                )->toArray()
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

                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('secondary'),

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
            ->filters([
                Tables\Filters\SelectFilter::make('guard_name')
                    ->options([
                        'web' => 'Web',
                        'api' => 'API',
                    ]),
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Permission')
                    ->modalDescription('Are you sure you want to delete this permission? This action cannot be undone and will remove this permission from all roles and users.')
                    ->modalSubmitActionLabel('Yes, delete it'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
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
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'view' => Pages\ViewPermission::route('/{record}'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Permissions';
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
