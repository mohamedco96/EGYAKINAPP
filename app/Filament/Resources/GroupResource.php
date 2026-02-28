<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GroupResource\Pages;
use App\Models\Group;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Groups';

    protected static ?string $navigationGroup = '📱 Social Feed';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('groups_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Group Information')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Group Name')
                    ->helperText('The name of the group'),
                Forms\Components\Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Group Owner')
                    ->getSearchResultsUsing(fn (string $search) => \App\Models\User::where(function($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('lname', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })->limit(50)->get()->pluck('full_name_with_email', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => \App\Models\User::find($value)?->full_name_with_email),
                Forms\Components\Select::make('privacy')
                    ->options([
                        'public' => 'Public',
                        'private' => 'Private',
                    ])
                    ->default('public')
                    ->required()
                    ->native(false)
                    ->label('Privacy Setting')
                    ->helperText('Public groups are visible to all, private groups require approval'),
                Forms\Components\Textarea::make('description')
                    ->nullable()
                    ->rows(3)
                    ->columnSpanFull()
                    ->label('Group Description'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['owner']))
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->badge()->color('gray')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),
                Tables\Columns\TextColumn::make('privacy')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'public' => 'success',
                        'private' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'public' => 'heroicon-o-globe-alt',
                        'private' => 'heroicon-o-lock-closed',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner')
                    ->formatStateUsing(fn ($record) => $record->owner ? $record->owner->name . ' ' . ($record->owner->lname ?? '') : 'N/A')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(50)
                    ->wrap()
                    ->placeholder('No description')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('created_at')->label('Created')->dateTime()->sortable()->since()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('privacy')
                    ->options([
                        'public' => 'Public',
                        'private' => 'Private',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->after(fn () => Cache::forget('groups_count')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->after(fn () => Cache::forget('groups_count')),
                    ExportBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'view' => Pages\ViewGroup::route('/{record}'),
            'edit' => Pages\EditGroup::route('/{record}/edit'),
        ];
    }
}