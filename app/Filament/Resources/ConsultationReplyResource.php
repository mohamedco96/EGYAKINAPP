<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultationReplyResource\Pages\ListConsultationReplies;
use App\Filament\Resources\ConsultationReplyResource\Pages\ViewConsultationReply;
use App\Modules\Consultations\Models\ConsultationReply;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ConsultationReplyResource extends Resource
{
    protected static ?string $model = ConsultationReply::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?string $navigationLabel = 'Consultation Replies';

    protected static string|\UnitEnum|null $navigationGroup = '💬 AI & Consultations';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('consultation_replies_count', 300, fn () => static::getModel()::count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Reply')->schema([
                Select::make('consultation_doctor_id')->relationship('consultationDoctor', 'id')->searchable()->preload()->required(),
                Textarea::make('reply')->required()->rows(5)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['consultationDoctor']))
            ->columns([
                TextColumn::make('id')->badge()->color('gray'),
                TextColumn::make('consultationDoctor.id')->label('Consultation Doctor')->sortable(),
                TextColumn::make('reply')->limit(60)->wrap(),
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
            ->emptyStateHeading('No consultation replies')
            ->emptyStateDescription('Doctor consultation replies will appear here.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-ellipsis');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsultationReplies::route('/'),
            'view' => ViewConsultationReply::route('/{record}'),
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
