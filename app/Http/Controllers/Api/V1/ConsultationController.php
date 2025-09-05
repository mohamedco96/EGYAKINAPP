<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Consultations\Controllers\ConsultationController as ModuleConsultationController;
use App\Modules\Consultations\Requests\AddDoctorsToConsultationRequest;
use App\Modules\Consultations\Requests\StoreConsultationRequest;
use App\Modules\Consultations\Requests\ToggleConsultationStatusRequest;
use App\Modules\Consultations\Requests\UpdateConsultationRequest;

class ConsultationController extends Controller
{
    protected $consultationController;

    public function __construct(ModuleConsultationController $consultationController)
    {
        $this->consultationController = $consultationController;
    }

    public function store(StoreConsultationRequest $request)
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

    public function update(UpdateConsultationRequest $request, $id)
    {
        return $this->consultationController->update($request, $id);
    }

    public function consultationSearch($data)
    {
        return $this->consultationController->consultationSearch($data);
    }

    public function addDoctors(AddDoctorsToConsultationRequest $request, $id)
    {
        return $this->consultationController->addDoctors($request, $id);
    }

    public function toggleStatus(ToggleConsultationStatusRequest $request, $id)
    {
        return $this->consultationController->toggleStatus($request, $id);
    }

    public function getMembers($id)
    {
        return $this->consultationController->getMembers($id);
    }

    public function addReply(UpdateConsultationRequest $request, $id)
    {
        return $this->consultationController->addReply($request, $id);
    }

    public function removeDoctor($consultationId, $doctorId)
    {
        return $this->consultationController->removeDoctor($consultationId, $doctorId);
    }
}
