<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultationDoctorResource\Pages\ListConsultationDoctors;
use App\Filament\Resources\ConsultationDoctorResource\Pages\ViewConsultationDoctor;
use App\Modules\Consultations\Models\ConsultationDoctor;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ConsultationDoctorResource extends Resource
{
    protected static ?string $model = ConsultationDoctor::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Consultation Doctors';

    protected static string|\UnitEnum|null $navigationGroup = '💬 AI & Consultations';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('consultation_doctors_count', 300, fn () => static::getModel()::count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Assignment')->schema([
                Select::make('consultation_id')->relationship('consultation', 'id')->searchable()->preload()->required(),
                Select::make('doctor_id')->relationship('doctor', 'name')->searchable()->preload()->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['consultation', 'consultDoctor']))
            ->columns([
                TextColumn::make('id')->badge()->color('gray'),
                TextColumn::make('consultation.id')->label('Consultation')->sortable(),
                TextColumn::make('consultDoctor.name')->label('Doctor')->searchable()->sortable(),
                TextColumn::make('created_at')->dateTime()->since()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No doctor assignments')
            ->emptyStateDescription('Consultation doctor assignments will appear here.')
            ->emptyStateIcon('heroicon-o-user-plus');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsultationDoctors::route('/'),
            'view' => ViewConsultationDoctor::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
