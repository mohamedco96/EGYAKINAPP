<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\SettingsController as V1SettingsController;
use App\Modules\Settings\Requests\StoreSettingsRequest;
use App\Modules\Settings\Requests\UpdateSettingsRequest;

class SettingsController extends Controller
{
    protected $settingsController;

    public function __construct(V1SettingsController $settingsController)
    {
        $this->settingsController = $settingsController;
    }

    public function index()
    {
        return $this->settingsController->index();
    }

    public function store(StoreSettingsRequest $request)
    {
        return $this->settingsController->store($request);
    }

    public function show($settings)
    {
        return $this->settingsController->show($settings);
    }

    public function update(UpdateSettingsRequest $request, $settings)
    {
        return $this->settingsController->update($request, $settings);
    }

    public function destroy($settings)
    {
        return $this->settingsController->destroy($settings);
    }
}
