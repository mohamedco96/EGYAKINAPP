<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostsResource\Pages;
use App\Models\User;
use App\Modules\Posts\Models\Posts;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PostsResource extends Resource
{
    protected static ?string $model = Posts::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Posts';

    protected static ?string $navigationGroup = '📝 Content Management';

    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')->required(),
                Forms\Components\TextInput::make('image')->required(),
                //Forms\Components\FileUpload::make('image')->image()->imageEditor(),
                Forms\Components\RichEditor::make('content')->required(),
                Forms\Components\Radio::make('hidden')->boolean()->required(),
                Forms\Components\Select::make('doctor_id')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Doctor Name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->toggleable(isToggledHiddenByDefault: false)->searchable(),
                Tables\Columns\TextColumn::make('title')->toggleable(isToggledHiddenByDefault: false)->searchable(),
                Tables\Columns\TextColumn::make('doctor.name')->toggleable(isToggledHiddenByDefault: false)->label('Doctor Name')->searchable(),
                Tables\Columns\ImageColumn::make('image')->toggleable(isToggledHiddenByDefault: false)->circular(),
                Tables\Columns\TextColumn::make('content')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\ToggleColumn::make('hidden')->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('created_at')->toggleable(isToggledHiddenByDefault: false)->label('Created At'),
                Tables\Columns\TextColumn::make('updated_at')->toggleable(isToggledHiddenByDefault: false)->label('Updated At'),
            ])
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                Tables\Filters\SelectFilter::make('Doctor Name')
                    ->options(fn (): array => User::query()->pluck('name', 'id')->all()),
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
                Tables\Actions\DeleteAction::make(),            ])
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePosts::route('/create'),
            'edit' => Pages\EditPosts::route('/{record}/edit'),
        ];
    }
}
