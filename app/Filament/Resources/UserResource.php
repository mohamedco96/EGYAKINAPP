<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-s-user';

    protected static ?string $navigationLabel = 'Doctors info.';

    protected static ?string $navigationGroup = 'Doctors';

    protected static ?int $navigationSort = 1;


    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->label('First Name'),
                Forms\Components\TextInput::make('lname')->required()->label('Last Name'),
                Forms\Components\TextInput::make('email')->required()->email()->label('Email address'),
                Forms\Components\TextInput::make('age'),
                Forms\Components\TextInput::make('specialty')->required(),
                Forms\Components\TextInput::make('workingplace')->required()->label('Working place'),
                Forms\Components\TextInput::make('phone')->required()->tel(),
                Forms\Components\TextInput::make('job')->required(),
                Forms\Components\Select::make('gender')
                    ->options([
                        'Male' => 'Male',
                        'Female' => 'Female',
                    ]),
                Forms\Components\TextInput::make('highestdegree')->required()->label('Highest degree'),
                Forms\Components\TextInput::make('registration_number')->required()->label('Registration Number'),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\Radio::make('blocked')->boolean(),
                Forms\Components\Radio::make('limited')->boolean(),
                FileUpload::make('image')
                    ->label('Profile Image')
                    ->directory('profile_images')
                    ->image()
                    ->imageEditor()
                    ->previewable(true)
                    ->imageCropAspectRatio('1:1')
                    ->imagePreviewHeight('250')
                    ->default(fn ($record) => $record->image ? asset('storage/profile_images/' . $record->image) : null),

                FileUpload::make('syndicate_card')
                    ->label('Syndicate Card')
                    ->directory('syndicate_card')
                    ->image()
                    ->imageEditor()
                    ->previewable(true)
                    ->imageCropAspectRatio('1:1')
                    ->imagePreviewHeight('250')
                    ->default(fn ($record) => $record->syndicate_card ? asset('storage/syndicate_card/' . $record->syndicate_card) : ''),


                Forms\Components\Select::make('isSyndicateCardRequired')
                    ->label('Is Syndicate Card Required')
                    ->options([
                        'Not Required' => 'Not Required',
                        'Required' => 'Required',
                        'Pending' => 'Pending',
                        'Verified' => 'Verified',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->searchable()->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('name')->label('First name')->searchable()->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('lname')->label('Last name')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('email')->searchable()->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\ImageColumn::make('image')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->circular()
                    ->extraAttributes(['style' => 'cursor: pointer;'])
                    ->action(function ($record) {
                        // Action to open the image in a new tab
                        return redirect()->away(asset('storage/profile_images/' . $record->image));
                    }),
                Tables\Columns\ImageColumn::make('syndicate_card')
                    ->label('Syndicate Card')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->circular()
                    ->extraAttributes(['style' => 'cursor: pointer;'])
                    ->action(function ($record) {
                        // Action to open the image in a new tab
                        return redirect()->away(asset('storage/syndicate_card/' . $record->image));
                    }),
                Tables\Columns\TextColumn::make('isSyndicateCardRequired')
                    ->label('Is Syndicate Card Required')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('age')->sortable()->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('specialty')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('workingplace')->label('Working place')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('phone')->searchable()->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('job')->searchable()->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('gender')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('highestdegree')->label('Highest degree')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('registration_number')->label('Registration Number')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\ToggleColumn::make('blocked')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\ToggleColumn::make('limited')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('created_at')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('email_verified_at')->toggleable(isToggledHiddenByDefault: false),

            ])
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email verification')
                    ->nullable()
                    ->placeholder('All users')
                    ->trueLabel('Verified users')
                    ->falseLabel('Not verified users'),

                Tables\Filters\SelectFilter::make('id')->label('Doctor Name')
                    ->options(fn (): array => User::query()->pluck('name', 'id')->all()),
                Tables\Filters\Filter::make('created_at')
                    ->form([
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
            ->deselectAllRecordsWhenFiltered(true)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
