<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Controllers\SettingsController as ModuleSettingsController;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    protected $settingsController;

    public function __construct(ModuleSettingsController $settingsController)
    {
        $this->settingsController = $settingsController;
    }

    public function index()
    {
        return $this->settingsController->index();
    }

    public function store(Request $request)
    {
        return $this->settingsController->store($request);
    }

    public function show($settings)
    {
        return $this->settingsController->show($settings);
    }

    public function update(Request $request, $settings)
    {
        return $this->settingsController->update($request, $settings);
    }

    public function destroy($settings)
    {
        return $this->settingsController->destroy($settings);
    }
}
