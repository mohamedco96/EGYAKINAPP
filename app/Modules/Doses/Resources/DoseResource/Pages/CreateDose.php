<?php

namespace App\Modules\Doses\Resources\DoseResource\Pages;

use App\Modules\Doses\Resources\DoseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDose extends CreateRecord
{
    protected static string $resource = DoseResource::class;
}
