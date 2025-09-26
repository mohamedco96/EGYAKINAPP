<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CauseResource\Pages;
use App\Models\Cause;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class CauseResource extends Resource
{
    protected static ?string $model = Cause::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?string $navigationLabel = 'AKI Causes';

    protected static ?string $navigationGroup = '🏥 Patient Management';

    protected static ?int $navigationSort = 50;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('doctor_id')
                    ->relationship('doctor', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('patient_id')
                    ->relationship('patient', 'id')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('cause_of_AKI')
                    ->label('Cause of AKI')
                    ->options([
                        'pre-renal' => 'Pre-renal',
                        'renal' => 'Renal',
                        'post-renal' => 'Post-renal',
                    ])
                    ->required(),

                Forms\Components\Section::make('Pre-renal Causes')
                    ->schema([
                        Forms\Components\TagsInput::make('pre-renal_causes')
                            ->label('Pre-renal Causes'),

                        Forms\Components\Textarea::make('pre-renal_others')
                            ->label('Other Pre-renal Causes'),
                    ])->columns(2),

                Forms\Components\Section::make('Renal Causes')
                    ->schema([
                        Forms\Components\TagsInput::make('renal_causes')
                            ->label('Renal Causes'),

                        Forms\Components\Textarea::make('renal_others')
                            ->label('Other Renal Causes'),
                    ])->columns(2),

                Forms\Components\Section::make('Post-renal Causes')
                    ->schema([
                        Forms\Components\TagsInput::make('post-renal_causes')
                            ->label('Post-renal Causes'),

                        Forms\Components\Textarea::make('post-renal_others')
                            ->label('Other Post-renal Causes'),
                    ])->columns(2),

                Forms\Components\Textarea::make('other')
                    ->label('Other Information'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('patient.id')
                    ->label('Patient ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cause_of_AKI')
                    ->label('AKI Cause Type')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('cause_of_AKI')
                    ->options([
                        'pre-renal' => 'Pre-renal',
                        'renal' => 'Renal',
                        'post-renal' => 'Post-renal',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
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
            'index' => Pages\ListCauses::route('/'),
            'create' => Pages\CreateCause::route('/create'),
            'edit' => Pages\EditCause::route('/{record}/edit'),
        ];
    }
}
