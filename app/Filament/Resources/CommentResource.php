<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Modules\Comments\Models\Comment;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?string $navigationLabel = 'Patient Comments';

    protected static ?string $navigationGroup = '🏥 Patient Management';

    protected static ?int $navigationSort = 60;

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
                    ->relationship('patient', 'id')
                    ->getSearchResultsUsing(function (string $search) {
                        return \App\Models\Patient::query()
                            ->where('id', 'like', "%{$search}%")
                            ->orWhereHas('doctor', function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('lname', 'like', "%{$search}%");
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($patient) {
                                $doctorName = $patient->doctor ? $patient->doctor->name . ' ' . ($patient->doctor->lname ?? '') : 'Unknown';
                                return [$patient->id => "Patient #{$patient->id} (Doctor: {$doctorName})"];
                            });
                    })
                    ->getOptionLabelUsing(function ($value) {
                        $patient = \App\Models\Patient::find($value);
                        if (!$patient) return "Patient #{$value}";
                        $doctorName = $patient->doctor ? $patient->doctor->name . ' ' . ($patient->doctor->lname ?? '') : 'Unknown';
                        return "Patient #{$patient->id} (Doctor: {$doctorName})";
                    })
                    ->searchable()
                    ->preload()
                    ->label('Patient'),
                Forms\Components\TextInput::make('content')->required()->label('Content'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['doctor']))
            ->columns([
                Tables\Columns\TextColumn::make('id')->toggleable(isToggledHiddenByDefault: false)->searchable(),
                Tables\Columns\TextColumn::make('doctor.name')->toggleable(isToggledHiddenByDefault: false)->label('Doctor Name')->searchable(),
                Tables\Columns\TextColumn::make('patient_id')->toggleable(isToggledHiddenByDefault: false)->label('Patient ID')->searchable(),
                Tables\Columns\TextColumn::make('content')->toggleable(isToggledHiddenByDefault: false)->label('Content'),
                Tables\Columns\TextColumn::make('created_at')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')->toggleable(isToggledHiddenByDefault: false),
            ])
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                Tables\Filters\SelectFilter::make('Doctor Name')
                    ->relationship('doctor', 'name'),
                Tables\Filters\SelectFilter::make('Patient')
                    ->relationship('patient', 'id')
                    ->getOptionLabelUsing(function ($value) {
                        $patient = \App\Models\Patient::find($value);
                        if (!$patient) return "Patient #{$value}";
                        $doctorName = $patient->doctor ? $patient->doctor->name . ' ' . ($patient->doctor->lname ?? '') : 'Unknown';
                        return "Patient #{$patient->id} (Doctor: {$doctorName})";
                    })
                    ->searchable()
                    ->preload(),
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
            'index' => Pages\ListComments::route('/'),
            'create' => Pages\CreateComment::route('/create'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }
}
