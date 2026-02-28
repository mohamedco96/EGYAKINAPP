<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultationDoctorResource\Pages;
use App\Modules\Consultations\Models\ConsultationDoctor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ConsultationDoctorResource extends Resource
{
    protected static ?string $model = ConsultationDoctor::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationLabel = 'Consultation Doctors';
    protected static ?string $navigationGroup = '💬 AI & Consultations';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('consultation_doctors_count', 300, fn() => static::getModel()::count());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Assignment')->schema([
                Forms\Components\Select::make('consultation_id')->relationship('consultation', 'id')->searchable()->preload()->required(),
                Forms\Components\Select::make('doctor_id')->relationship('doctor', 'name')->searchable()->preload()->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['consultation', 'consultDoctor']))
            ->columns([
                Tables\Columns\TextColumn::make('id')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('consultation.id')->label('Consultation')->sortable(),
                Tables\Columns\TextColumn::make('doctor.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsultationDoctors::route('/'),
            'view' => Pages\ViewConsultationDoctor::route('/{record}'),
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
