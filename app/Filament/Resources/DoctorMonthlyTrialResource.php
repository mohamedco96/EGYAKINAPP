<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DoctorMonthlyTrialResource\Pages;
use App\Modules\Chat\Models\DoctorMonthlyTrial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class DoctorMonthlyTrialResource extends Resource
{
    protected static ?string $model = DoctorMonthlyTrial::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Monthly AI Trials';
    protected static ?string $navigationGroup = '💬 AI & Consultations';
    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('doctor_monthly_trials_count', 300, fn() => static::getModel()::count());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Trial Information')->schema([
                Forms\Components\Select::make('doctor_id')->relationship('doctor', 'name')->searchable()->preload()->required(),
                Forms\Components\TextInput::make('month')->required()->maxLength(255),
                Forms\Components\TextInput::make('trials_used')->numeric()->default(0)->required(),
                Forms\Components\TextInput::make('trials_limit')->numeric()->default(10)->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('doctor.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('month')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('trials_used')->badge()->color('info')->sortable(),
                Tables\Columns\TextColumn::make('trials_limit')->badge()->color('success')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make(), ExportBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDoctorMonthlyTrials::route('/'),
            'create' => Pages\CreateDoctorMonthlyTrial::route('/create'),
            'view' => Pages\ViewDoctorMonthlyTrial::route('/{record}'),
            'edit' => Pages\EditDoctorMonthlyTrial::route('/{record}/edit'),
        ];
    }
}
