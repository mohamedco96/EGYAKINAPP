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

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-s-user';

    protected static ?string $navigationLabel = 'Doctors';

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
                //Forms\Components\TextInput::make('password')->required()->password(),
                Forms\Components\TextInput::make('age'),
                Forms\Components\TextInput::make('specialty')->required(),
                Forms\Components\TextInput::make('workingplace')->required()->label('Working place'),
                Forms\Components\TextInput::make('phone')->required()->tel(),
                Forms\Components\TextInput::make('job')->required(),
                Forms\Components\TextInput::make('highestdegree')->required()->label('Highest degree'),
                Forms\Components\Radio::make('blocked')->boolean(),
                Forms\Components\Radio::make('limited')->boolean(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->searchable(),
                Tables\Columns\TextColumn::make('name')->label('First name')->searchable(),
                Tables\Columns\TextColumn::make('lname')->label('Last name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('age'),
                Tables\Columns\TextColumn::make('specialty'),
                Tables\Columns\TextColumn::make('workingplace')->label('Working place'),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('job'),
                Tables\Columns\TextColumn::make('highestdegree')->label('Highest degree'),
                Tables\Columns\TextColumn::make('blocked'),
                Tables\Columns\TextColumn::make('limited'),
                Tables\Columns\TextColumn::make('created_at')->label('Created At'),

            ])
            ->filters([
                //Tables\Filters\SelectFilter::make('name'),
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
            ])->filtersTriggerAction(
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
