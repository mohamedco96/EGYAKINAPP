<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultationReplyResource\Pages;
use App\Modules\Consultations\Models\ConsultationReply;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ConsultationReplyResource extends Resource
{
    protected static ?string $model = ConsultationReply::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static ?string $navigationLabel = 'Consultation Replies';
    protected static ?string $navigationGroup = '💬 AI & Consultations';
    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('consultation_replies_count', 300, fn() => static::getModel()::count());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Reply')->schema([
                Forms\Components\Select::make('consultation_doctor_id')->relationship('consultationDoctor', 'id')->searchable()->preload()->required(),
                Forms\Components\Textarea::make('reply')->required()->rows(5)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('consultationDoctor.id')->label('Consultation Doctor')->sortable(),
                Tables\Columns\TextColumn::make('reply')->limit(60)->wrap(),
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
            'index' => Pages\ListConsultationReplies::route('/'),
            'view' => Pages\ViewConsultationReply::route('/{record}'),
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
