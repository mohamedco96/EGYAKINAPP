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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PostsResource extends Resource
{
    protected static ?string $model = Posts::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Posts';

    protected static ?string $navigationGroup = '📝 Content Management';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('posts_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Post Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->label('Title')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('doctor_id')
                            ->relationship('doctor', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Doctor Name'),

                        Forms\Components\Radio::make('hidden')
                            ->label('Visibility Status')
                            ->boolean()
                            ->required()
                            ->inline()
                            ->options([
                                false => 'Published',
                                true => 'Hidden',
                            ])
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Content')
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->required()
                            ->label('Post Content')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'link',
                                'heading',
                                'bulletList',
                                'orderedList',
                                'blockquote',
                                'codeBlock',
                            ]),
                    ]),

                Forms\Components\Section::make('Media')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label('Post Image')
                            ->image()
                            ->imageEditor()
                            ->directory('post_images')
                            ->required()
                            ->previewable(true)
                            ->imageCropAspectRatio('16:9')
                            ->imagePreviewHeight('250')
                            ->helperText('Upload an image for the post (recommended: 16:9 aspect ratio)'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 50) {
                            return $state;
                        }
                        return null;
                    })
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('hidden')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Hidden' : 'Published')
                    ->color(fn ($state) => $state ? 'danger' : 'success')
                    ->icon(fn ($state) => $state ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),

                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Doctor Name')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(['users.name', 'users.lname'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->doctor ? $record->doctor->name . ' ' . $record->doctor->lname : 'N/A')
                    ->description(fn ($record) => $record->doctor?->email),

                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->circular()
                    ->size(50),

                Tables\Columns\TextColumn::make('content')
                    ->label('Content')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable()
                    ->limit(100)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = strip_tags($column->getState());
                        if (strlen($state) > 100) {
                            return $state;
                        }
                        return null;
                    })
                    ->html()
                    ->formatStateUsing(fn ($state) => strip_tags($state)),

                Tables\Columns\TextColumn::make('postcomments_count')
                    ->label('Comments')
                    ->counts('postcomments')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('M d, Y H:i:s')),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                Tables\Filters\TernaryFilter::make('hidden')
                    ->label('Visibility Status')
                    ->placeholder('All posts')
                    ->trueLabel('Hidden only')
                    ->falseLabel('Published only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('hidden', true),
                        false: fn (Builder $query) => $query->where('hidden', false),
                    ),

                Tables\Filters\SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('From'),
                        DatePicker::make('created_until')
                            ->label('Until'),
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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'From ' . \Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Until ' . \Carbon\Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
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
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Post Details')
                    ->modalWidth('4xl'),
                Tables\Actions\Action::make('toggleVisibility')
                    ->label(fn ($record) => $record->hidden ? 'Publish' : 'Hide')
                    ->icon(fn ($record) => $record->hidden ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->color(fn ($record) => $record->hidden ? 'success' : 'warning')
                    ->action(function ($record) {
                        $record->update(['hidden' => !$record->hidden]);
                    })
                    ->successNotificationTitle(fn ($record) => $record->hidden ? 'Post hidden' : 'Post published'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        Cache::forget('posts_count');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Publish Posts')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each->update(['hidden' => false]);
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Selected posts published'),
                    Tables\Actions\BulkAction::make('hide')
                        ->label('Hide Posts')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each->update(['hidden' => true]);
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Selected posts hidden'),
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('posts_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->emptyStateHeading('No posts yet')
            ->emptyStateDescription('Posts created by doctors will appear here.')
            ->emptyStateIcon('heroicon-o-document-text');
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
            'view' => Pages\ViewPosts::route('/{record}'),
            'edit' => Pages\EditPosts::route('/{record}/edit'),
        ];
    }
}
