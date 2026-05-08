<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages\CreateComment;
use App\Filament\Resources\CommentResource\Pages\EditComment;
use App\Filament\Resources\CommentResource\Pages\ListComments;
use App\Modules\Comments\Models\Comment;
use App\Modules\Patients\Models\Patients;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?string $navigationLabel = 'Patient Comments';

    protected static string|\UnitEnum|null $navigationGroup = '🏥 Patient Management';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('comments_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('doctor_id')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Doctor Name'),
                Select::make('patient_id')
                    ->relationship('patient', 'id')
                    ->getSearchResultsUsing(function (string $search) {
                        return Patients::query()
                            ->with('doctor')
                            ->where('id', 'like', "%{$search}%")
                            ->orWhereHas('doctor', function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('lname', 'like', "%{$search}%");
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($patient) {
                                $doctorName = $patient->doctor ? $patient->doctor->name.' '.($patient->doctor->lname ?? '') : 'Unknown';

                                return [$patient->id => "Patient #{$patient->id} (Doctor: {$doctorName})"];
                            });
                    })
                    ->getOptionLabelUsing(function ($value) {
                        $patient = Patients::with('doctor')->find($value);
                        if (! $patient) {
                            return "Patient #{$value}";
                        }
                        $doctorName = $patient->doctor ? $patient->doctor->name.' '.($patient->doctor->lname ?? '') : 'Unknown';

                        return "Patient #{$patient->id} (Doctor: {$doctorName})";
                    })
                    ->searchable()
                    ->preload()
                    ->label('Patient'),
                TextInput::make('content')->required()->label('Content'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['doctor']))
            ->columns([
                TextColumn::make('id')->toggleable(isToggledHiddenByDefault: false)->searchable(),
                TextColumn::make('doctor.name')->toggleable(isToggledHiddenByDefault: false)->label('Doctor Name')->searchable(),
                TextColumn::make('patient_id')->toggleable(isToggledHiddenByDefault: false)->label('Patient ID')->searchable(),
                TextColumn::make('content')->toggleable(isToggledHiddenByDefault: false)->label('Content'),
                TextColumn::make('created_at')->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('updated_at')->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                SelectFilter::make('Doctor Name')
                    ->relationship('doctor', 'name'),
                SelectFilter::make('Patient')
                    ->relationship('patient', 'id')
                    ->getOptionLabelUsing(function ($value) {
                        $patient = Patients::with('doctor')->find($value);
                        if (! $patient) {
                            return "Patient #{$value}";
                        }
                        $doctorName = $patient->doctor ? $patient->doctor->name.' '.($patient->doctor->lname ?? '') : 'Unknown';

                        return "Patient #{$patient->id} (Doctor: {$doctorName})";
                    })
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->schema([
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
            ->deferFilters(false)
            ->deselectAllRecordsWhenFiltered(true)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
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
            'index' => ListComments::route('/'),
            'create' => CreateComment::route('/create'),
            'edit' => EditComment::route('/{record}/edit'),
        ];
    }
}
