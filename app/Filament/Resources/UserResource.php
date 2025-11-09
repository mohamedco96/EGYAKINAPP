<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
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

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Doctors';

    protected static ?string $navigationGroup = '👥 User Management';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('users_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Personal Information')
                    ->description('Basic user information')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required()->label('First Name'),
                        Forms\Components\TextInput::make('lname')->required()->label('Last Name'),
                        Forms\Components\TextInput::make('email')->required()->email()->label('Email address'),
                        Forms\Components\TextInput::make('age'),
                        Forms\Components\Select::make('gender')
                            ->options([
                                'Male' => 'Male',
                                'Female' => 'Female',
                            ]),
                        Forms\Components\TextInput::make('phone')->required()->tel(),
                    ])->columns(2),

                Section::make('Professional Information')
                    ->description('Professional details')
                    ->schema([
                        Forms\Components\TextInput::make('specialty')->required(),
                        Forms\Components\TextInput::make('workingplace')->required()->label('Working place'),
                        Forms\Components\TextInput::make('job')->required(),
                        Forms\Components\TextInput::make('highestdegree')->required()->label('Highest degree'),
                        Forms\Components\TextInput::make('registration_number')->required()->label('Registration Number'),
                    ])->columns(2),

                Section::make('Account Status')
                    ->description('Account verification and status')
                    ->schema([
                        Forms\Components\DateTimePicker::make('email_verified_at'),
                        Forms\Components\Radio::make('blocked')->boolean(),
                        Forms\Components\Radio::make('limited')->boolean(),
                        Forms\Components\Select::make('isSyndicateCardRequired')
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
                            ->image()
                            ->imageEditor()
                            ->previewable(true)
                            ->imageCropAspectRatio('1:1')
                            ->imagePreviewHeight('250'),

                        FileUpload::make('syndicate_card')
                            ->label('Syndicate Card')
                            ->directory('syndicate_card')
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
                            ->columns(3)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->searchable()
                            ->getOptionLabelUsing(fn ($value): string => ucwords(str_replace(['-', '_'], ' ', Role::find($value)?->name ?? '')))
                            ->descriptions(
                                fn (): array => Role::all()->pluck('name', 'id')->map(
                                    fn ($name) => 'Assign '.ucwords(str_replace(['-', '_'], ' ', $name)).' role'
                                )->toArray()
                            ),

                        CheckboxList::make('permissions')
                            ->relationship('permissions', 'name')
                            ->columns(3)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->searchable()
                            ->getOptionLabelUsing(fn ($value): string => ucwords(str_replace(['-', '_'], ' ', Permission::find($value)?->name ?? '')))
                            ->descriptions(
                                fn (): array => Permission::all()->pluck('name', 'id')->map(
                                    fn ($name) => 'Grant '.str_replace(['-', '_'], ' ', $name).' permission'
                                )->toArray()
                            ),
                    ]),
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
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable(['name', 'lname'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->name . ' ' . $record->lname)
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-envelope')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\ImageColumn::make('image')
                    ->label('Profile')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->circular()
                    ->size(40),

                Tables\Columns\TextColumn::make('specialty')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('job')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('gender')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state === 'Male' ? 'info' : 'success')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\IconColumn::make('blocked')
                    ->label('Blocked')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\IconColumn::make('limited')
                    ->label('Limited')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('patients_count')
                    ->label('Patients')
                    ->counts('patients')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-users')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('posts_count')
                    ->label('Posts')
                    ->counts('posts')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-document-text')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('isSyndicateCardRequired')
                    ->label('Syndicate Card')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'Verified' => 'success',
                        'Pending' => 'warning',
                        'Required' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-phone')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('age')
                    ->sortable()
                    ->suffix(' yrs')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('workingplace')
                    ->label('Working Place')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('highestdegree')
                    ->label('Highest Degree')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('registration_number')
                    ->label('Registration #')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace(['-', '_'], ' ', $state)))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Verified')
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s'))
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email Verification')
                    ->nullable()
                    ->placeholder('All users')
                    ->trueLabel('Verified users')
                    ->falseLabel('Not verified users'),

                Tables\Filters\TernaryFilter::make('blocked')
                    ->label('Blocked Status')
                    ->placeholder('All users')
                    ->trueLabel('Blocked only')
                    ->falseLabel('Not blocked only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('blocked', true),
                        false: fn (Builder $query) => $query->where('blocked', false),
                    ),

                Tables\Filters\TernaryFilter::make('limited')
                    ->label('Limited Status')
                    ->placeholder('All users')
                    ->trueLabel('Limited only')
                    ->falseLabel('Not limited only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('limited', true),
                        false: fn (Builder $query) => $query->where('limited', false),
                    ),

                Tables\Filters\SelectFilter::make('specialty')
                    ->label('Specialty')
                    ->options(fn (): array => User::query()
                        ->whereNotNull('specialty')
                        ->distinct()
                        ->pluck('specialty', 'specialty')
                        ->toArray())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('gender')
                    ->label('Gender')
                    ->options([
                        'Male' => 'Male',
                        'Female' => 'Female',
                    ]),

                Tables\Filters\SelectFilter::make('job')
                    ->label('Job')
                    ->options(fn (): array => User::query()
                        ->whereNotNull('job')
                        ->distinct()
                        ->pluck('job', 'job')
                        ->toArray())
                    ->searchable(),

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
                            $indicators[] = 'From ' . \Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Until ' . \Carbon\Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(5)
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
                    ->modalHeading('User Details')
                    ->modalWidth('4xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        Cache::forget('users_count');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('block')
                        ->label('Block Users')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update(['blocked' => true]);
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Selected users blocked'),
                    Tables\Actions\BulkAction::make('unblock')
                        ->label('Unblock Users')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each->update(['blocked' => false]);
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Selected users unblocked'),
                    Tables\Actions\BulkAction::make('assignRole')
                        ->label('Assign Role')
                        ->icon('heroicon-o-user-group')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('role')
                                ->label('Select Role')
                                ->options(fn (): array => Role::all()->pluck('name', 'name')->toArray())
                                ->required()
                                ->searchable(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each(function ($record) use ($data) {
                                $record->assignRole($data['role']);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Role assigned successfully'),
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('users_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
