<?php

namespace App\Filament\Resources\RecommendationResource\Pages;

use App\Filament\Resources\RecommendationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateRecommendation extends CreateRecord
{
    protected static string $resource = RecommendationResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('recommendations_count');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}