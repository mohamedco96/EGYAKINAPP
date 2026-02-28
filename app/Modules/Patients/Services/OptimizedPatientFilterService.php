<?php

namespace App\Modules\Patients\Services;

use App\Modules\Patients\Models\Patients;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OptimizedPatientFilterService
{
    private $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
    }

    /**
     * Get patients for doctor with OPTIMIZED pagination
     *
     * PERFORMANCE IMPROVEMENTS:
     * 1. Proper database-level pagination (no memory loading)
     * 2. Selective eager loading (only needed answers/status)
     * 3. Optimized queries with proper indexes
     * 4. Caching for repeated requests
     */
    public function getDoctorPatients(bool $allPatients = false, int $perPage = 10): Paginator
    {
        $user = Auth::user();
        $isAdminOrTester = $user->hasRole('admin') || $user->hasRole('tester');

        // Create cache key for this specific request
        $cacheKey = "doctor_patients_{$user->id}_{$allPatients}_{$perPage}_".request('page', 1);

        // Try to get from cache first (5 minutes cache)
        return Cache::remember($cacheKey, 300, function () use ($allPatients, $user, $isAdminOrTester, $perPage) {
            return $this->buildOptimizedQuery($allPatients, $user, $isAdminOrTester, $perPage);
        });
    }

    /**
     * Build optimized query with proper pagination
     */
    private function buildOptimizedQuery(bool $allPatients, $user, bool $isAdminOrTester, int $perPage): Paginator
    {
        // Base query with only essential fields
        $query = $allPatients
            ? Patients::select('id', 'doctor_id', 'updated_at')
            : $user->patients()->select('id', 'doctor_id', 'updated_at');

        // Apply visibility filter
        $query->when(! $isAdminOrTester, function ($query) {
            return $query->where('hidden', false);
        });

        // CRITICAL: Use database-level pagination, not memory loading
        $patients = $query
            ->with([
                'doctor:id,name,lname,image,syndicate_card,isSyndicateCardRequired,version',
                // OPTIMIZATION: Only load specific answers we need (name=1, hospital=2)
                'answers' => function ($query) {
                    $query->select('id', 'patient_id', 'answer', 'question_id')
                        ->whereIn('question_id', [1, 2]); // Only name and hospital
                },
                // OPTIMIZATION: Only load specific status we need
                'status' => function ($query) {
                    $query->select('id', 'patient_id', 'key', 'status')
                        ->whereIn('key', ['submit_status', 'outcome_status']);
                },
            ])
            ->latest('updated_at')
            ->paginate($perPage); // PROPER pagination at database level

        // Transform the paginated results
        $patients->getCollection()->transform(function ($patient) {
            return $this->transformPatientDataOptimized($patient);
        });

        return $patients;
    }

    /**
     * OPTIMIZED patient data transformation
     */
    private function transformPatientDataOptimized($patient): array
    {
        // Create indexed collections for O(1) lookups
        $statusByKey = $patient->status->keyBy('key');
        $answersByQuestionId = $patient->answers->keyBy('question_id');

        // Get specific values efficiently
        $submitStatus = optional($statusByKey->get('submit_status'))->status ?? false;
        $outcomeStatus = optional($statusByKey->get('outcome_status'))->status ?? false;

        // Only get the answers we actually need
        $nameAnswer = optional($answersByQuestionId->get(1))->answer;
        $hospitalAnswer = optional($answersByQuestionId->get(2))->answer;

        return [
            'id' => $patient->id,
            'doctor_id' => (int) $patient->doctor_id,
            'name' => $nameAnswer,
            'hospital' => $hospitalAnswer,
            'updated_at' => $patient->updated_at,
            'doctor' => $patient->doctor,
            'sections' => [
                'patient_id' => $patient->id,
                'submit_status' => $submitStatus,
                'outcome_status' => $outcomeStatus,
            ],
        ];
    }

    /**
     * SUPER FAST version for high-traffic scenarios using raw SQL
     */
    public function getDoctorPatientsUltraFast(bool $allPatients = false, int $perPage = 10): array
    {
        $user = Auth::user();
        $isAdminOrTester = $user->hasRole('admin') || $user->hasRole('tester');

        // Build WHERE conditions
        $whereConditions = [];
        $bindings = [];

        if (! $allPatients) {
            $whereConditions[] = 'p.doctor_id = ?';
            $bindings[] = $user->id;
        }

        if (! $isAdminOrTester) {
            $whereConditions[] = 'p.hidden = 0';
        }

        $whereClause = ! empty($whereConditions) ? 'WHERE '.implode(' AND ', $whereConditions) : '';

        $offset = (request('page', 1) - 1) * $perPage;

        // OPTIMIZED RAW SQL with JOINs instead of N+1 queries
        $sql = "
            SELECT 
                p.id,
                p.doctor_id,
                p.updated_at,
                u.name as doctor_name,
                u.lname as doctor_lname,
                u.image as doctor_image,
                u.syndicate_card as doctor_syndicate_card,
                u.isSyndicateCardRequired as doctor_syndicate_required,
                u.version as doctor_version,
                -- Get name (question_id = 1)
                name_answer.answer as patient_name,
                -- Get hospital (question_id = 2)  
                hospital_answer.answer as patient_hospital,
                -- Get submit status
                submit_status.status as submit_status,
                -- Get outcome status
                outcome_status.status as outcome_status
            FROM patients p
            LEFT JOIN users u ON p.doctor_id = u.id
            LEFT JOIN answers name_answer ON p.id = name_answer.patient_id AND name_answer.question_id = 1
            LEFT JOIN answers hospital_answer ON p.id = hospital_answer.patient_id AND hospital_answer.question_id = 2
            LEFT JOIN patient_statuses submit_status ON p.id = submit_status.patient_id AND submit_status.key = 'submit_status'
            LEFT JOIN patient_statuses outcome_status ON p.id = outcome_status.patient_id AND outcome_status.key = 'outcome_status'
            {$whereClause}
            ORDER BY p.updated_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $patients = DB::select($sql, $bindings);

        // Get total count for pagination
        $countSql = "
            SELECT COUNT(DISTINCT p.id) as total
            FROM patients p
            {$whereClause}
        ";

        $totalCount = DB::select($countSql, $bindings)[0]->total;

        // Transform results
        $transformedPatients = collect($patients)->map(function ($patient) {
            return [
                'id' => $patient->id,
                'doctor_id' => (int) $patient->doctor_id,
                'name' => $patient->patient_name,
                'hospital' => $patient->patient_hospital,
                'updated_at' => $patient->updated_at,
                'doctor' => [
                    'id' => $patient->doctor_id,
                    'name' => $patient->doctor_name,
                    'lname' => $patient->doctor_lname,
                    'image' => $patient->doctor_image,
                    'syndicate_card' => $patient->doctor_syndicate_card,
                    'isSyndicateCardRequired' => $patient->doctor_syndicate_required,
                    'version' => $patient->doctor_version,
                ],
                'sections' => [
                    'patient_id' => $patient->id,
                    'submit_status' => (bool) $patient->submit_status,
                    'outcome_status' => (bool) $patient->outcome_status,
                ],
            ];
        });

        // Create pagination manually
        $currentPage = request('page', 1);
        $paginatedPatients = new Paginator(
            $transformedPatients,
            $totalCount,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );

        return $paginatedPatients;
    }

    /**
     * Clear cache when patients are updated
     */
    public function clearPatientsCache(int $doctorId): void
    {
        $patterns = [
            "doctor_patients_{$doctorId}_*",
            'patient_filters_*',
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}
