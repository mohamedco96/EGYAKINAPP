<?php

namespace App\Modules\Patients\Services;

use App\Modules\Patients\Models\Patients;
use App\Modules\Patients\Services\PatientService;
use App\Models\Questions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class PatientFilterService
{
    private $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
    }

    /**
     * Filter patients based on various criteria
     */
    public function filterPatients(array $filters, int $perPage = 10, int $page = 1): array
    {
        $paginationParams = ['page', 'per_page', 'sort', 'direction', 'offset', 'limit'];
        $cleanFilters = collect($filters)->except($paginationParams);

        $patientsQuery = Patients::select('id', 'doctor_id', 'updated_at')
            ->where('hidden', false);

        $this->applyFilters($patientsQuery, $cleanFilters);

        $patients = $patientsQuery->with([
            'doctor:id,name,lname,image,syndicate_card,isSyndicateCardRequired',
            'status:id,patient_id,key,status',
            'answers:id,patient_id,answer,question_id'
        ])
        ->latest('updated_at')
        ->paginate($perPage, ['*'], 'page', $page);

        $transformedPatients = $patients->map(function ($patient) {
            return $this->transformPatientData($patient);
        });

        return [
            'data' => $transformedPatients,
            'pagination' => [
                'total' => $patients->total(),
                'per_page' => $patients->perPage(),
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
                'from' => $patients->firstItem(),
                'to' => $patients->lastItem(),
            ]
        ];
    }

    /**
     * Get patients for doctor with pagination
     */
    public function getDoctorPatients(bool $allPatients = false): \Illuminate\Pagination\LengthAwarePaginator
    {
        $user = Auth::user();
        $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');

        $query = $allPatients 
            ? Patients::select('id', 'doctor_id', 'updated_at')
            : $user->patients()->select('id', 'doctor_id', 'updated_at');

        $patients = $query->when(!$isAdminOrTester, function ($query) {
                return $query->where('hidden', false);
            })
            ->with([
                'doctor:id,name,lname,image,syndicate_card,isSyndicateCardRequired,version',
                'status:id,patient_id,key,status',
                'answers:id,patient_id,answer,question_id'
            ])
            ->latest('updated_at')
            ->get();

        $transformedPatients = $patients->map(function ($patient) {
            return $this->transformPatientData($patient);
        });

        $currentPage = Paginator::resolveCurrentPage();
        $slicedData = $transformedPatients->slice(($currentPage - 1) * 10, 10);
        $paginatedPatients = new Paginator($slicedData->values(), count($transformedPatients), 10);

        return $paginatedPatients;
    }

    /**
     * Apply filters to the patient query
     */
    private function applyFilters($query, $filters): void
    {
        $filters->each(function ($value, $questionID) use ($query) {
            if (str_starts_with((string)$questionID, '00') || is_null($value)) {
                return;
            }

            if ($questionID == 9901) {
                // Handle submit_status filter
                $query->whereHas('status', function ($statusQuery) use ($value) {
                    $booleanValue = ($value === 'Yes');
                    $statusQuery->where('key', 'submit_status')
                        ->where('status', $booleanValue);
                });
            } elseif ($questionID == 9902) {
                // Handle outcome_status filter
                $query->whereHas('status', function ($statusQuery) use ($value) {
                    $booleanValue = ($value === 'Yes');
                    $statusQuery->where('key', 'outcome_status')
                        ->where('status', $booleanValue);
                });
            } else {
                // Handle answer filters
                $query->whereHas('answers', function ($answerQuery) use ($questionID, $value) {
                    $quotedValue = '"' . $value . '"';
                    $answerQuery->where('question_id', $questionID)
                        ->where('answer', $quotedValue);
                });
            }
        });
    }

    /**
     * Transform patient data for response
     */
    private function transformPatientData($patient): array
    {
        $submitStatus = optional($patient->status->where('key', 'submit_status')->first())->status;
        $outcomeStatus = optional($patient->status->where('key', 'outcome_status')->first())->status;

        $nameAnswer = optional($patient->answers->where('question_id', 1)->first())->answer;
        $hospitalAnswer = optional($patient->answers->where('question_id', 2)->first())->answer;

        return [
            'id' => $patient->id,
            'doctor_id' => (int)$patient->doctor_id,
            'name' => $nameAnswer,
            'hospital' => $hospitalAnswer,
            'updated_at' => $patient->updated_at,
            'doctor' => $patient->doctor,
            'sections' => [
                'patient_id' => $patient->id,
                'submit_status' => $submitStatus ?? false,
                'outcome_status' => $outcomeStatus ?? false,
            ]
        ];
    }
}
