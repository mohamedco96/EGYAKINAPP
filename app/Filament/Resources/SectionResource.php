<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SectionResource\Pages;
use App\Filament\Resources\SectionResource\RelationManagers;
use App\Models\Section;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SectionResource extends Resource
{
    protected static ?string $model = Section::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Sections';
    protected static ?string $navigationGroup = 'Other';
    protected static ?int $navigationSort = 4;
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
                    ->searchable()
                    ->preload()
                    ->label('Doctor Name'),
                Forms\Components\Select::make('patient_id')
                    ->relationship('patient', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Patient Name'),
                Forms\Components\Radio::make('section_1')
                    ->label('Section 1 status')
                    ->boolean(),
                Forms\Components\Radio::make('section_2')
                    ->label('Section 2 status')
                    ->boolean(),
                Forms\Components\Radio::make('section_3')
                    ->label('Section 3 status')
                    ->boolean(),
                Forms\Components\Radio::make('section_4')
                    ->label('Section 4 status')
                    ->boolean(),
                Forms\Components\Radio::make('section_5')
                    ->label('Section 5 status')
                    ->boolean(),
                Forms\Components\Radio::make('section_6')
                    ->label('Section 6 status')
                    ->boolean(),
                Forms\Components\Radio::make('section_7')
                    ->label('Section 7 status')
                    ->boolean(),
                Forms\Components\Radio::make('submit_status')
                    ->label('Submit status')
                    ->boolean(),
                Forms\Components\Radio::make('outcome_status')
                    ->label('Outcome status')
                    ->boolean(),
                Forms\Components\Select::make('doc_id')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Doctor that do Outcome'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->searchable(),
                Tables\Columns\TextColumn::make('doctor.name')->label('Doctor Name')->searchable(),
                Tables\Columns\TextColumn::make('patient.name')->label('Patient Name')->searchable(),
                Tables\Columns\TextColumn::make('section_1'),
                Tables\Columns\TextColumn::make('section_2'),
                Tables\Columns\TextColumn::make('section_3'),
                Tables\Columns\TextColumn::make('section_4'),
                Tables\Columns\TextColumn::make('section_5'),
                Tables\Columns\TextColumn::make('section_6'),
                Tables\Columns\TextColumn::make('section_7'),
                Tables\Columns\TextColumn::make('submit_status'),
                Tables\Columns\TextColumn::make('outcome_status'),
                Tables\Columns\TextColumn::make('doc_id'),
                Tables\Columns\TextColumn::make('created_at')->label('Created At'),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated At'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('Doctor Name')
                ->relationship('doctor', 'name'),
                Tables\Filters\SelectFilter::make('Patient Name')
                    ->relationship('patient', 'name'),
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
                    })
            ])->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'),)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()
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
            'index' => Pages\ListSections::route('/'),
            'create' => Pages\CreateSection::route('/create'),
            'edit' => Pages\EditSection::route('/{record}/edit'),
        ];
    }
}
