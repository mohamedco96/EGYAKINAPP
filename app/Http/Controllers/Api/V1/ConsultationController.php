<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Consultations\Controllers\ConsultationController as ModuleConsultationController;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    protected $consultationController;

    public function __construct(ModuleConsultationController $consultationController)
    {
        $this->consultationController = $consultationController;
    }

    public function store(Request $request)
    {
        return $this->consultationController->store($request);
    }

    public function sentRequests()
    {
        return $this->consultationController->sentRequests();
    }

    public function receivedRequests()
    {
        return $this->consultationController->receivedRequests();
    }

    public function consultationDetails($id)
    {
        return $this->consultationController->consultationDetails($id);
    }

    public function update(Request $request, $id)
    {
        return $this->consultationController->update($request, $id);
    }

    public function consultationSearch($data)
    {
        return $this->consultationController->consultationSearch($data);
    }
}
