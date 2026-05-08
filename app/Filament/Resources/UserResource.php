<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Doctors';

    protected static string|\UnitEnum|null $navigationGroup = '⚙️ Administration';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('users_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Information')
                    ->description('Basic user information')
                    ->schema([
                        TextInput::make('name')->required()->label('First Name'),
                        TextInput::make('lname')->required()->label('Last Name'),
                        TextInput::make('email')->required()->email()->label('Email address'),
                        TextInput::make('age'),
                        Select::make('gender')
                            ->options([
                                'Male' => 'Male',
                                'Female' => 'Female',
                            ]),
                        TextInput::make('phone')->required()->tel(),
                    ])->columns(2),

                Section::make('Professional Information')
                    ->description('Professional details')
                    ->schema([
                        TextInput::make('specialty')->required(),
                        TextInput::make('workingplace')->required()->label('Working place'),
                        TextInput::make('job')->required(),
                        TextInput::make('highestdegree')->required()->label('Highest degree'),
                        TextInput::make('registration_number')->required()->label('Registration Number'),
                    ])->columns(2),

                Section::make('Account Status')
                    ->description('Account verification and status')
                    ->schema([
                        DateTimePicker::make('email_verified_at'),
                        Radio::make('blocked')->boolean(),
                        Radio::make('limited')->boolean(),
                        Select::make('isSyndicateCardRequired')
                            ->label('Is Syndicate Card Required')
                            ->options([
                                'Not Required' => 'Not Required',
                                'Required' => 'Required',
                                'Pending' => 'Pending',
                                'Verified' => 'Verified',
                            ]),
                    ])->columns(2),

                Section::make('Profile Images')
                    ->description('Upload profile and syndicate card images')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Profile Image')
                            ->directory('profile_images')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->previewable(true)
                            ->imageCropAspectRatio('1:1')
                            ->imagePreviewHeight('250'),

                        FileUpload::make('syndicate_card')
                            ->label('Syndicate Card')
                            ->directory('syndicate_card')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->previewable(true)
                            ->imageCropAspectRatio('1:1')
                            ->imagePreviewHeight('250'),
                    ])->columns(2),

                Section::make('Roles & Permissions')
                    ->description('Assign roles and permissions to this user')
                    ->schema([
                        CheckboxList::make('roles')
                            ->relationship('roles', 'name')
                            ->options(
                                fn (): array => Role::all()->pluck('name', 'id')->map(
                                    fn ($name) => ucwords(str_replace(['-', '_'], ' ', $name))
                                )->toArray()
                            )
                            ->columns(3)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->searchable()
                            ->descriptions(
                                fn (): array => Role::all()->pluck('name', 'id')->map(
                                    fn ($name) => 'Assign '.ucwords(str_replace(['-', '_'], ' ', $name)).' role'
                                )->toArray()
                            )
                            ->live()
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    static::validateUserPermissionConflicts($value ?? [], $get('permissions') ?? [], $fail);
                                },
                            ]),

                        CheckboxList::make('permissions')
                            ->relationship('permissions', 'name')
                            ->options(
                                fn (): array => Permission::all()->pluck('name', 'id')->map(
                                    fn ($name) => ucwords(str_replace(['-', '_'], ' ', $name))
                                )->toArray()
                            )
                            ->columns(3)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->searchable()
                            ->descriptions(
                                fn (): array => Permission::all()->pluck('name', 'id')->map(
                                    fn ($name) => 'Grant '.str_replace(['-', '_'], ' ', $name).' permission'
                                )->toArray()
                            )
                            ->live()
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    static::validateUserPermissionConflicts($get('roles') ?? [], $value ?? [], $fail);
                                },
                            ]),
                    ]),
            ]);
    }

    /**
     * Mutually exclusive permission pairs (kept in sync with RoleResource::conflictPairs).
     */
    private static function conflictPairs(): array
    {
        return [
            [
                'a' => ['view-all-patients', 'view-current-patients'],
                'b' => ['view-groups-in-home', 'view-trend-hashtags-in-home'],
                'message' => 'Patient management permissions and home content permissions are mutually exclusive.',
            ],
            [
                'a' => ['add-post-in-home'],
                'b' => ['add-patient-in-home'],
                'message' => 'add-post-in-home and add-patient-in-home cannot be assigned together.',
            ],
        ];
    }

    /**
     * Validates that a user's combined permissions (via roles + direct) do not violate
     * any conflict pair defined in conflictPairs().
     */
    private static function validateUserPermissionConflicts(array $roleIds, array $directPermissionIds, Closure $fail): void
    {
        // Collect permission names from selected roles
        $rolePermissionNames = Role::whereIn('id', $roleIds)
            ->with('permissions')
            ->get()
            ->flatMap(fn ($role) => $role->permissions)
            ->pluck('name')
            ->toArray();

        // Collect direct permission names
        $directPermissionNames = Permission::whereIn('id', $directPermissionIds)->pluck('name')->toArray();

        $allPermissions = array_unique(array_merge($rolePermissionNames, $directPermissionNames));

        foreach (static::conflictPairs() as $pair) {
            $matchA = array_values(array_intersect($allPermissions, $pair['a']));
            $matchB = array_values(array_intersect($allPermissions, $pair['b']));

            if ($matchA && $matchB) {
                $fail(
                    'Permission conflict: ['.implode(', ', $matchA).'] cannot be combined with ['.implode(', ', $matchB).']. '
                    .$pair['message']
                );
            }
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['roles']))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable(['name', 'lname'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->name.' '.$record->lname)
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-envelope')
                    ->toggleable(isToggledHiddenByDefault: false),

                ImageColumn::make('image')
                    ->label('Profile')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->circular()
                    ->size(40),

                TextColumn::make('specialty')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('job')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('gender')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state === 'Male' ? 'info' : 'success')
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('blocked')
                    ->label('Blocked')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('limited')
                    ->label('Limited')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('patients_count')
                    ->label('Patients')
                    ->counts('patients')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-users')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('posts_count')
                    ->label('Posts')
                    ->counts('posts')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-document-text')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('isSyndicateCardRequired')
                    ->label('Syndicate Card')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Verified' => 'success',
                        'Pending' => 'warning',
                        'Required' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('phone')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-phone')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('age')
                    ->sortable()
                    ->suffix(' yrs')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('workingplace')
                    ->label('Working Place')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('highestdegree')
                    ->label('Highest Degree')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('registration_number')
                    ->label('Registration #')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace(['-', '_'], ' ', $state)))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('email_verified_at')
                    ->label('Verified')
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                TernaryFilter::make('email_verified_at')
                    ->label('Email Verification')
                    ->nullable()
                    ->placeholder('All users')
                    ->trueLabel('Verified users')
                    ->falseLabel('Not verified users'),

                TernaryFilter::make('blocked')
                    ->label('Blocked Status')
                    ->placeholder('All users')
                    ->trueLabel('Blocked only')
                    ->falseLabel('Not blocked only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('blocked', true),
                        false: fn (Builder $query) => $query->where('blocked', false),
                    ),

                TernaryFilter::make('limited')
                    ->label('Limited Status')
                    ->placeholder('All users')
                    ->trueLabel('Limited only')
                    ->falseLabel('Not limited only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('limited', true),
                        false: fn (Builder $query) => $query->where('limited', false),
                    ),

                SelectFilter::make('specialty')
                    ->label('Specialty')
                    ->options(fn (): array => User::query()
                        ->whereNotNull('specialty')
                        ->distinct()
                        ->pluck('specialty', 'specialty')
                        ->toArray())
                    ->searchable(),

                SelectFilter::make('gender')
                    ->label('Gender')
                    ->options([
                        'Male' => 'Male',
                        'Female' => 'Female',
                    ]),

                SelectFilter::make('job')
                    ->label('Job')
                    ->options(fn (): array => User::query()
                        ->whereNotNull('job')
                        ->distinct()
                        ->pluck('job', 'job')
                        ->toArray())
                    ->searchable(),

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
                ViewAction::make()
                    ->modalHeading('User Details')
                    ->modalWidth('4xl'),
                EditAction::make(),
                DeleteAction::make()
                    ->after(function () {
                        Cache::forget('users_count');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('block')
                        ->label('Block Users')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update(['blocked' => true]);
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Selected users blocked'),
                    BulkAction::make('unblock')
                        ->label('Unblock Users')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each->update(['blocked' => false]);
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Selected users unblocked'),
                    BulkAction::make('assignRole')
                        ->label('Assign Role')
                        ->icon('heroicon-o-user-group')
                        ->color('info')
                        ->form([
                            Select::make('role')
                                ->label('Select Role')
                                ->options(fn (): array => Role::all()->pluck('name', 'name')->toArray())
                                ->required()
                                ->searchable(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each(function ($record) use ($data) {
                                $record->assignRole($data['role']);
                                $record->update(['permissions_changed' => true]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Role assigned successfully'),
                    DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('users_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->emptyStateHeading('No doctors yet')
            ->emptyStateDescription('Registered doctors will appear here.')
            ->emptyStateIcon('heroicon-o-users');
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
