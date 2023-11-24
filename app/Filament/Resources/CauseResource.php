<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CauseResource\Pages;
use App\Models\Cause;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class CauseResource extends Resource
{
    protected static ?string $model = Cause::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Cause';

    protected static ?string $navigationGroup = 'Patient Sections';

    protected static ?int $navigationSort = 6;

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
                Forms\Components\Select::make('cause_of_AKI')->label('Cause of AKI')
                    ->options([
                        'Pre-renal' => 'Pre-renal',
                        'Intrinsic renal' => 'Intrinsic renal',
                        'Post-renal' => 'Post-renal',
                    ])->searchable(),
                Forms\Components\Select::make('pre-renal_causes')
                    ->label('Pre-renal causes of AKI in this patient include')
                    ->multiple()
                    ->options([
                        'Volume depletion due to hemorrhage' => 'Volume depletion due to hemorrhage',
                        'Volume depletion due to vomiting' => 'Volume depletion due to vomiting',
                        'Volume depletion due to diarrhea' => 'Volume depletion due to diarrhea',
                        'Volume depletion due to burns' => 'Volume depletion due to burns',
                        'Volume depletion due to inappropriate diuresis' => 'Volume depletion due to inappropriate diuresis',
                        'Edematous state due to HF' => 'Edematous state due to HF',
                        'Edematous state due to LCF' => 'Edematous state due to LCF',
                        'Edematous state due to Nephrotic syndrome' => 'Edematous state due to Nephrotic syndrome',
                        'Edematous state due to other causes' => 'Edematous state due to other causes',
                        'Hypotension due to septic shock' => 'Hypotension due to septic shock',
                        'Hypotension due to cardiogenic shock' => 'Hypotension due to cardiogenic shock',
                        'Hypotension due to anaphylactic shock' => 'Hypotension due to anaphylactic shock',
                        'Renal hypoperfusion due to NSAIDs use' => 'Renal hypoperfusion due to NSAIDs use',
                        'Renal hypoperfusion due to ACEi/ARBs use' => 'Renal hypoperfusion due to ACEi/ARBs use',
                        'Renal hypoperfusion due to abdominal aortic aneurysm' => 'Renal hypoperfusion due to abdominal aortic aneurysm',
                        'Renal hypoperfusion due to renal artery stenosis/occlusion' => 'Renal hypoperfusion due to renal artery stenosis/occlusion',
                        'Renal hypoperfusion due to hepatorenal syndrome' => 'Renal hypoperfusion due to hepatorenal syndrome',
                        'other causes' => 'other causes',
                    ]),
                Forms\Components\TextInput::make('pre-renal_others')->label('If the cause of pre-renal AKI is others, what is the cause?'),
                Forms\Components\TextInput::make('renal_causes')->label('Intrinsic renal causes of AKI in this patient include'),
                Forms\Components\TextInput::make('renal_others')->label('If the cause of intrinsic renal AKI is others, what is the cause?'),
                Forms\Components\TextInput::make('post-renal_causes')->label('Post-renal causes of AKI in this patient include'),
                Forms\Components\TextInput::make('post-renal_others')->label('If the cause of post-renal AKI is others, what is the cause?'),
                Forms\Components\TextInput::make('other'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->searchable(),
                Tables\Columns\TextColumn::make('doctor.name')->label('Doctor Name')->searchable(),
                Tables\Columns\TextColumn::make('patient.name')->label('Patient Name')->searchable(),
                Tables\Columns\TextColumn::make('cause_of_AKI')->label('Cause of AKI'),
                Tables\Columns\TextColumn::make('pre-renal_causes')->label('Pre-renal causes of AKI in this patient include'),
                Tables\Columns\TextColumn::make('pre-renal_others')->label('If the cause of pre-renal AKI is others, what is the cause?'),
                Tables\Columns\TextColumn::make('renal_causes')->label('Intrinsic renal causes of AKI in this patient include'),
                Tables\Columns\TextColumn::make('renal_others')->label('If the cause of intrinsic renal AKI is others, what is the cause?'),
                Tables\Columns\TextColumn::make('post-renal_causes')->label('Post-renal causes of AKI in this patient include'),
                Tables\Columns\TextColumn::make('post-renal_others')->label('If the cause of post-renal AKI is others, what is the cause?'),
                Tables\Columns\TextColumn::make('other')->label('Other Causes'),
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
                    }),
            ])->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'), )
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
            'index' => Pages\ListCauses::route('/'),
            'create' => Pages\CreateCause::route('/create'),
            'edit' => Pages\EditCause::route('/{record}/edit'),
        ];
    }
}
