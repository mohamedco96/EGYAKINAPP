<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V1\PatientsController as V1PatientsController;
use App\Http\Controllers\Controller;
use App\Modules\Patients\Requests\UpdatePatientsRequest;
use Illuminate\Http\Request;

class PatientsController extends Controller
{
    protected $patientsController;

    public function __construct(V1PatientsController $patientsController)
    {
        $this->patientsController = $patientsController;
    }

    public function storePatient(Request $request)
    {
        return $this->patientsController->storePatient($request);
    }

    public function updateFinalSubmit(Request $request, $patient_id)
    {
        return $this->patientsController->updateFinalSubmit($request, $patient_id);
    }

    public function updatePatient(UpdatePatientsRequest $request, $section_id, $patient_id)
    {
        return $this->patientsController->updatePatient($request, $section_id, $patient_id);
    }

    public function destroyPatient($id)
    {
        return $this->patientsController->destroyPatient($id);
    }

    public function searchNew(Request $request)
    {
        return $this->patientsController->searchNew($request);
    }

    public function homeGetAllData()
    {
        return $this->patientsController->homeGetAllData();
    }

    public function doctorPatientGet()
    {
        return $this->patientsController->doctorPatientGet();
    }

    public function doctorPatientGetAll()
    {
        return $this->patientsController->doctorPatientGetAll();
    }

    public function test()
    {
        return $this->patientsController->test();
    }

    public function uploadFile(Request $request)
    {
        return $this->patientsController->uploadFile($request);
    }

    public function uploadFileNew(Request $request)
    {
        return $this->patientsController->uploadFileNew($request);
    }

    public function patientFilterConditions()
    {
        return $this->patientsController->patientFilterConditions();
    }

    public function filteredPatients(Request $request)
    {
        return $this->patientsController->filteredPatients($request);
    }

    public function exportFilteredPatients()
    {
        return $this->patientsController->exportFilteredPatients();
    }

    public function generatePatientPDF($patient_id)
    {
        return $this->patientsController->generatePatientPDF($patient_id);
    }
}
