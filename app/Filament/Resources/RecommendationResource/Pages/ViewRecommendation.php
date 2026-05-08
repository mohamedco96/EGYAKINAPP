<?php

namespace App\Filament\Resources\RecommendationResource\Pages;

use App\Filament\Resources\RecommendationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRecommendation extends ViewRecord
{
    protected static string $resource = RecommendationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
