<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostsResource\Pages\CreatePosts;
use App\Filament\Resources\PostsResource\Pages\EditPosts;
use App\Filament\Resources\PostsResource\Pages\ListPosts;
use App\Filament\Resources\PostsResource\Pages\ViewPosts;
use App\Modules\Posts\Models\Posts;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PostsResource extends Resource
{
    protected static ?string $model = Posts::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Posts';

    protected static string|\UnitEnum|null $navigationGroup = '📱 Community';

    protected static ?int $navigationSort = 6;

    public static function getNavigationBadge(): ?string
    {
        return Cache::remember('posts_count', 300, function () {
            return static::getModel()::count();
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Post Information')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->label('Title')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('doctor_id')
                            ->relationship('doctor', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Doctor Name'),

                        Radio::make('hidden')
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

                Section::make('Content')
                    ->schema([
                        RichEditor::make('content')
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

                Section::make('Media')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Post Image')
                            ->image()
                            ->imageEditor()
                            ->directory('post_images')
                            ->visibility('public')
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['doctor']))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Title')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 50) {
                            return $state;
                        }

                        return null;
                    })
                    ->weight('bold'),

                TextColumn::make('hidden')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Hidden' : 'Published')
                    ->color(fn ($state) => $state ? 'danger' : 'success')
                    ->icon(fn ($state) => $state ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),

                TextColumn::make('doctor.name')
                    ->label('Doctor Name')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable(['users.name', 'users.lname'])
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->doctor ? $record->doctor->name.' '.$record->doctor->lname : 'N/A')
                    ->description(fn ($record) => $record->doctor?->email),

                ImageColumn::make('image')
                    ->label('Image')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->circular()
                    ->size(50),

                TextColumn::make('content')
                    ->label('Content')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->searchable()
                    ->sortable()
                    ->limit(100)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = strip_tags($column->getState());
                        if (strlen($state) > 100) {
                            return $state;
                        }

                        return null;
                    })
                    ->html()
                    ->formatStateUsing(fn ($state) => strip_tags($state)),

                TextColumn::make('postcomments_count')
                    ->label('Comments')
                    ->counts('postcomments')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('M d, Y H:i:s')),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('M d, Y H:i:s')),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->striped()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistSortInSession()
            ->filters([
                TernaryFilter::make('hidden')
                    ->label('Visibility Status')
                    ->placeholder('All posts')
                    ->trueLabel('Hidden only')
                    ->falseLabel('Published only')
                    ->queries(
                        true: fn (Builder $query) => $query->where('hidden', true),
                        false: fn (Builder $query) => $query->where('hidden', false),
                    ),

                SelectFilter::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('created_at')
                    ->schema([
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
                            $indicators[] = 'From '.Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Until '.Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
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
                ViewAction::make()
                    ->modalHeading('Post Details')
                    ->modalWidth('4xl'),
                Action::make('toggleVisibility')
                    ->label(fn ($record) => $record->hidden ? 'Publish' : 'Hide')
                    ->icon(fn ($record) => $record->hidden ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->color(fn ($record) => $record->hidden ? 'success' : 'warning')
                    ->action(function ($record) {
                        $record->update(['hidden' => ! $record->hidden]);
                    })
                    ->successNotificationTitle(fn ($record) => $record->hidden ? 'Post hidden' : 'Post published'),
                EditAction::make(),
                DeleteAction::make()
                    ->after(function () {
                        Cache::forget('posts_count');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Publish Posts')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each->update(['hidden' => false]);
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Selected posts published'),
                    BulkAction::make('hide')
                        ->label('Hide Posts')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each->update(['hidden' => true]);
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Selected posts hidden'),
                    DeleteBulkAction::make()
                        ->after(function () {
                            Cache::forget('posts_count');
                        }),
                    ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
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
            'index' => ListPosts::route('/'),
            'create' => CreatePosts::route('/create'),
            'view' => ViewPosts::route('/{record}'),
            'edit' => EditPosts::route('/{record}/edit'),
        ];
    }
}
