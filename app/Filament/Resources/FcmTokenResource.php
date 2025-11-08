<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FcmTokenResource\Pages;
use App\Modules\Notifications\Models\FcmToken;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class FcmTokenResource extends Resource
{
    protected static ?string $model = FcmToken::class;
    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationLabel = 'FCM Tokens';
    protected static ?string $navigationGroup = '🔒 System Administration';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('fcm_tokens_count', 300, fn() => static::getModel()::count());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Token Information')->schema([
                Forms\Components\Select::make('doctor_id')->relationship('doctor', 'name')->searchable()->preload()->required(),
                Forms\Components\Textarea::make('fcm_token')->required()->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('doctor.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('fcm_token')->limit(30)->copyable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make(), ExportBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFcmTokens::route('/'),
            'create' => Pages\CreateFcmToken::route('/create'),
            'view' => Pages\ViewFcmToken::route('/{record}'),
            'edit' => Pages\EditFcmToken::route('/{record}/edit'),
        ];
    }
}
