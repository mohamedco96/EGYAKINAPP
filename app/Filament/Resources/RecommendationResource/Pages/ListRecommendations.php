<?php

namespace App\Filament\Resources\RecommendationResource\Pages;

use App\Filament\Resources\RecommendationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListRecommendations extends ListRecords
{
    protected static string $resource = RecommendationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function afterDelete(): void
    {
        Cache::forget('recommendations_count');
    }
}
