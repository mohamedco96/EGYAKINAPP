<?php

namespace App\Modules\Patients\Services;

use App\Modules\Patients\Models\Patients;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Auth;

class PatientFilterService
{
    private $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
    }

    /**
     * Filter patients based on various criteria
     *
     * @param  array  $filters  Filter parameters
     * @param  int  $perPage  Number of items per page
     * @param  int  $page  Current page number
     * @param  bool  $onlyAuthUserPatients  If true, filters only authenticated user's patients
     */
    public function filterPatients(array $filters, int $perPage = 10, int $page = 1, bool $onlyAuthUserPatients = false): array
    {
        $paginationParams = ['page', 'per_page', 'sort', 'direction', 'offset', 'limit', 'only_my_patients'];
        $cleanFilters = collect($filters)->except($paginationParams);

        $patientsQuery = Patients::select('id', 'doctor_id', 'updated_at');

        // Filter by authenticated user's patients if requested
        if ($onlyAuthUserPatients) {
            // When filtering only user's own patients, include hidden patients
            $patientsQuery->where('doctor_id', Auth::id());
        } else {
            // When viewing all patients, exclude hidden patients
            $patientsQuery->where('hidden', false);
        }

        $this->applyFilters($patientsQuery, $cleanFilters);

        // If perPage is very large (used for exports), get all results without pagination
        if ($perPage >= PHP_INT_MAX) {
            $patients = $patientsQuery->with([
                'doctor:id,name,lname,image,syndicate_card,isSyndicateCardRequired',
                'status:id,patient_id,key,status',
                'answers:id,patient_id,answer,question_id',
            ])
                ->latest('updated_at')
                ->get();

            $transformedPatients = $patients->map(function ($patient) {
                return $this->transformPatientData($patient);
            });

            return [
                'data' => $transformedPatients,
                'pagination' => [
                    'total' => $patients->count(),
                    'per_page' => $patients->count(),
                    'current_page' => 1,
                    'last_page' => 1,
                    'from' => 1,
                    'to' => $patients->count(),
                ],
            ];
        }

        // Regular pagination
        $patients = $patientsQuery->with([
            'doctor:id,name,lname,image,syndicate_card,isSyndicateCardRequired',
            'status:id,patient_id,key,status',
            'answers:id,patient_id,answer,question_id',
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
            ],
        ];
    }

    /**
     * Get patients for doctor with pagination
     */
    public function getDoctorPatients(bool $allPatients = false): \Illuminate\Pagination\LengthAwarePaginator
    {
        $user = Auth::user();
        $isAdminOrTester = $user->hasRole('admin') || $user->hasRole('tester');

        $query = $allPatients
            ? Patients::select('id', 'doctor_id', 'updated_at')
            : $user->patients()->select('id', 'doctor_id', 'updated_at');

        $patients = $query->when(! $isAdminOrTester, function ($query) {
            return $query->where('hidden', false);
        })
            ->with([
                'doctor:id,name,lname,image,syndicate_card,isSyndicateCardRequired,version',
                'status:id,patient_id,key,status',
                'answers:id,patient_id,answer,question_id',
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
            if (str_starts_with((string) $questionID, '00') || is_null($value)) {
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
            } elseif ($questionID == 9903) {
                // Handle patient registration date range filter
                if (is_array($value)) {
                    // Expecting format: ['from' => '2024-01-01', 'to' => '2024-12-31']
                    if (! empty($value['from']) && $this->isValidDate($value['from'])) {
                        $query->whereDate('created_at', '>=', $value['from']);
                    }
                    if (! empty($value['to']) && $this->isValidDate($value['to'])) {
                        $query->whereDate('created_at', '<=', $value['to']);
                    }
                }
            } elseif ($questionID == 7 && is_array($value)) {
                // Handle age range filter (Question ID 7)
                // Expecting format: ['from' => '25', 'to' => '45']
                $query->whereHas('answers', function ($answerQuery) use ($value) {
                    $answerQuery->where('question_id', 7);

                    if (! empty($value['from']) && is_numeric($value['from'])) {
                        // Age stored as JSON string, e.g., "25"
                        $answerQuery->whereRaw('CAST(JSON_UNQUOTE(answer) AS UNSIGNED) >= ?', [(int) $value['from']]);
                    }

                    if (! empty($value['to']) && is_numeric($value['to'])) {
                        $answerQuery->whereRaw('CAST(JSON_UNQUOTE(answer) AS UNSIGNED) <= ?', [(int) $value['to']]);
                    }
                });
            } else {
                // Handle answer filters
                $query->whereHas('answers', function ($answerQuery) use ($questionID, $value) {
                    $quotedValue = '"'.$value.'"';
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
        // Create indexed collections for O(1) lookups instead of O(n) where() calls
        $statusByKey = $patient->status->keyBy('key');
        $answersByQuestionId = $patient->answers->keyBy('question_id');

        // Use indexed collections for efficient lookups
        $submitStatus = optional($statusByKey->get('submit_status'))->status;
        $outcomeStatus = optional($statusByKey->get('outcome_status'))->status;

        $nameAnswer = optional($answersByQuestionId->get(1))->answer;
        $hospitalAnswer = optional($answersByQuestionId->get(2))->answer;

        return [
            'id' => $patient->id,
            'doctor_id' => (int) $patient->doctor_id,
            'name' => $nameAnswer,
            'hospital' => $hospitalAnswer,
            'updated_at' => $patient->updated_at,
            'doctor' => $patient->doctor,
            'answers' => $patient->answers,
            'sections' => [
                'patient_id' => $patient->id,
                'submit_status' => $submitStatus ?? false,
                'outcome_status' => $outcomeStatus ?? false,
            ],
        ];
    }

    /**
     * Validate if a string is a valid date in Y-m-d format
     */
    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);

        return $d && $d->format('Y-m-d') === $date;
    }
}
