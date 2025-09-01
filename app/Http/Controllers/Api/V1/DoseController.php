<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Doses\Controllers\DoseController as ModuleDoseController;
use App\Modules\Doses\Requests\StoreDoseRequest;
use App\Modules\Doses\Requests\UpdateDoseRequest;
use Illuminate\Http\Request;

class DoseController extends Controller
{
    protected $doseController;

    public function __construct(ModuleDoseController $doseController)
    {
        $this->doseController = $doseController;
    }

    public function index()
    {
        return $this->doseController->index();
    }

    public function store(StoreDoseRequest $request)
    {
        return $this->doseController->store($request);
    }

    public function show($id)
    {
        return $this->doseController->show($id);
    }

    public function update(UpdateDoseRequest $request, $id)
    {
        return $this->doseController->update($request, $id);
    }

    public function destroy($id)
    {
        return $this->doseController->destroy($id);
    }

    public function doseSearch(Request $request, $query)
    {
        return $this->doseController->doseSearch($request, $query);
    }
}
