<?php

namespace App\Filament\Resources\PatientsResource\Pages;

use App\Filament\Resources\PatientsResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePatients extends CreateRecord
{
    protected static string $resource = PatientsResource::class;
}
