<?php

namespace App\Modules\Patients\Controllers;

use App\Events\SearchResultsUpdated;
use App\Http\Controllers\Controller;
use App\Modules\Patients\Requests\UpdatePatientsRequest;
use App\Modules\Patients\Services\MarkedPatientService;
use App\Modules\Patients\Services\PatientFilterService;
use App\Modules\Patients\Services\PatientService;
use App\Modules\Questions\Models\Questions;
use App\Services\FileUploadService;
use App\Services\HomeDataService;
use App\Services\PdfGenerationService;
use App\Services\QuestionService;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PatientsController extends Controller
{
    protected $patientService;

    protected $homeDataService;

    protected $searchService;

    protected $questionService;

    protected $fileUploadService;

    protected $patientFilterService;

    protected $pdfGenerationService;

    protected $markedPatientService;

    public function __construct(
        PatientService $patientService,
        HomeDataService $homeDataService,
        SearchService $searchService,
        QuestionService $questionService,
        FileUploadService $fileUploadService,
        PatientFilterService $patientFilterService,
        PdfGenerationService $pdfGenerationService,
        MarkedPatientService $markedPatientService
    ) {
        $this->patientService = $patientService;
        $this->homeDataService = $homeDataService;
        $this->searchService = $searchService;
        $this->questionService = $questionService;
        $this->fileUploadService = $fileUploadService;
        $this->patientFilterService = $patientFilterService;
        $this->pdfGenerationService = $pdfGenerationService;
        $this->markedPatientService = $markedPatientService;
    }

    /**
     * Handle the file upload.
     */
    public function uploadFile(Request $request)
    {
        $request->validate([
            //'file' => 'required|mimes:jpg,jpeg,png,pdf|max:2048', // Example validation
        ]);

        $result = $this->fileUploadService->uploadFile($request->file('file'));

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function uploadFileNew(Request $request)
    {
        try {
            $fileUrls = $this->fileUploadService->uploadMultipleFiles($request->all());

            return response()->json([
                'message' => 'Files uploaded successfully',
                'file_urls' => $fileUrls,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function homeGetAllData()
    {
        try {
            $homeData = $this->homeDataService->getHomeData();

            return response()->json($homeData, 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving home data.', [
                'user_id' => optional(auth()->user())->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to retrieve home data.',
            ], 500);
        }
    }

    public function doctorPatientGetAll()
    {
        try {
            $startTime = microtime(true);

            // Use optimized service for better performance
            $optimizedService = app(\App\Modules\Patients\Services\OptimizedPatientFilterService::class);
            $perPage = request('per_page', 10);

            // PERFORMANCE: Use the ultra-fast version for high traffic
            $paginatedPatients = $optimizedService->getDoctorPatients(true, $perPage);

            $filterConditions = $this->questionService->getFilterConditions();

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $response = [
                'value' => true,
                'filter' => $filterConditions,
                'data' => $paginatedPatients,
                'performance' => [
                    'execution_time_ms' => $executionTime,
                    'optimized' => true,
                ],
            ];

            Log::info('Successfully retrieved all patients.', [
                'user_id' => auth()->id(),
                'execution_time_ms' => $executionTime,
                'per_page' => $perPage,
                'total_patients' => $paginatedPatients->total(),
            ]);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving all patients.', [
                'user_id' => optional(auth()->user())->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => __('api.failed_to_retrieve_all_patients')], 500);
        }
    }

    public function doctorPatientGet()
    {
        try {
            $perPage = request('per_page', 10);
            $paginatedPatients = $this->patientFilterService->getDoctorPatients(false, $perPage);

            $user = auth()->user();
            $userPatientCount = $user->patients()->count();
            $scoreValue = optional($user->score)->score ?? 0;
            $isVerified = (bool) $user->email_verified_at;

            // Get filter conditions for authenticated user's patients
            $filterConditions = $this->questionService->getFilterConditions();

            $response = [
                'value' => true,
                'verified' => $isVerified,
                'patient_count' => strval($userPatientCount),
                'score_value' => strval($scoreValue),
                'filter' => $filterConditions,
                'data' => $paginatedPatients,
            ];

            Log::info('Successfully retrieved current doctor patients.', [
                'doctor_id' => optional(auth()->user())->id,
                'per_page' => $perPage,
            ]);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving current doctor patients.', [
                'doctor_id' => optional(auth()->user())->id,
                'exception' => $e,
            ]);

            return response()->json(['error' => __('api.failed_to_retrieve_current_doctor_patients')], 500);
        }
    }

    public function doctorProfileGetPatients()
    {
        try {
            $paginatedPatients = $this->patientFilterService->getDoctorPatients(false);

            $response = [
                'value' => true,
                'data' => $paginatedPatients,
            ];

            Log::info('Successfully retrieved doctor profile patients.', [
                'doctor_id' => optional(auth()->user())->id,
            ]);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving doctor profile patients.', [
                'doctor_id' => optional(auth()->user())->id,
                'exception' => $e,
            ]);

            return response()->json(['error' => __('api.failed_to_retrieve_doctor_profile_patients')], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storePatient(Request $request)
    {
        try {
            $response = $this->patientService->createPatient($request->all());

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error('Error while storing patient: '.$e->getMessage(), [
                'request' => $request->all(),
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.error_occurred', ['message' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function updatePatient(UpdatePatientsRequest $request, $section_id, $patient_id)
    {
        // Check if the section exists
        $sectionExists = Questions::where('section_id', $section_id)->exists();
        if (! $sectionExists) {
            return response()->json([
                'value' => false,
                'message' => __('api.section_not_found'),
            ], 404);
        }

        if (! $this->patientService->patientExists($patient_id)) {
            return response()->json([
                'value' => false,
                'message' => __('api.patient_not_found'),
            ], 404);
        }

        try {
            $response = $this->patientService->updatePatientSection($request->all(), $section_id, $patient_id);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error('Error while updating patient: '.$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => __('api.error_occurred', ['message' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroyPatient($id)
    {
        try {
            $response = $this->patientService->deletePatient($id);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error('Error deleting patient', [
                'patient_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Error occurred while deleting patient: '.$e->getMessage(),
            ], 500);
        }
    }

    public function searchNewold(Request $request)
    {
        try {
            $request->validate([
                'dose' => 'nullable|string|max:255',
                'patient' => 'nullable|string|max:255',
            ]);

            $doseQuery = $request->input('dose', '');
            $patientQuery = $request->input('patient', '');

            $searchResults = $this->searchService->search($patientQuery, $doseQuery);

            if (empty($patientQuery) && empty($doseQuery)) {
                Log::info('No search term provided.');

                return response()->json([
                    'value' => true,
                    'data' => [
                        'patients' => [],
                        'doses' => [],
                    ],
                ], 200);
            }

            // Paginate patients if needed
            $patients = $searchResults['patients'];
            if ($patients->isNotEmpty()) {
                $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
                $perPage = 10;
                $slicedData = $patients->slice(($currentPage - 1) * $perPage, $perPage);
                $paginatedPatients = new \Illuminate\Pagination\LengthAwarePaginator(
                    $slicedData->values(),
                    count($patients),
                    $perPage,
                    $currentPage,
                    ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()]
                );
            } else {
                $paginatedPatients = [];
            }

            Log::info('Successfully retrieved data for the search term.', ['search_term' => $patientQuery]);

            return response()->json([
                'value' => true,
                'data' => [
                    'patients' => $paginatedPatients,
                    'doses' => $searchResults['doses'],
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error searching for data.', ['exception' => $e]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to search for data.',
            ], 500);
        }
    }

    public function searchNew(Request $request)
    {
        try {
            $request->validate([
                'dose' => 'nullable|string|max:255',
                'patient' => 'nullable|string|max:255',
            ]);

            $doseQuery = $request->input('dose', '');
            $patientQuery = $request->input('patient', '');

            $searchResults = $this->searchService->search($patientQuery, $doseQuery);

            if (empty($patientQuery) && empty($doseQuery)) {
                Log::info('No search term provided.');

                return response()->json([
                    'value' => true,
                    'data' => [
                        'patients' => [],
                        'doses' => [],
                    ],
                ], 200);
            }

            Log::info('Successfully retrieved data for the search term.', ['search_term' => $patientQuery]);

            return response()->json([
                'value' => true,
                'data' => $searchResults,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error searching for data.', ['exception' => $e]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to search for data.',
            ], 500);
        }
    }

    public function realTimeSearch(Request $request)
    {
        try {
            $request->validate([
                'dose' => 'nullable|string|max:255',
                'patient' => 'nullable|string|max:255',
            ]);

            $doseQuery = $request->input('dose', '');
            $patientQuery = $request->input('patient', '');

            $searchResults = $this->searchService->search($patientQuery, $doseQuery);

            return response()->json([
                'value' => true,
                'data' => $searchResults,
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching for data.', ['exception' => $e]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to search for data.',
            ], 500);
        }
    }

    public function realTimeSearchold(Request $request)
    {
        try {
            $request->validate([
                'dose' => 'nullable|string|max:255',
                'patient' => 'nullable|string|max:255',
            ]);

            $doseQuery = $request->input('dose', '');
            $patientQuery = $request->input('patient', '');

            $searchResults = $this->searchService->search($patientQuery, $doseQuery);

            if (empty($patientQuery) && empty($doseQuery)) {
                Log::info('No search term provided.');
                $searchResults = [
                    'patients' => collect(),
                    'doses' => collect(),
                ];
            }

            // Broadcast search results
            broadcast(new SearchResultsUpdated($searchResults));

            Log::info('Successfully retrieved data for the search term.', ['search_term' => $patientQuery]);

            // Return view with the data
            return view('search', ['data' => $searchResults]);
        } catch (\Exception $e) {
            Log::error('Error searching for data.', ['exception' => $e]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to search for data.',
            ], 500);
        }
    }

    public function generatePatientPDF($patient_id)
    {
        try {
            $result = $this->pdfGenerationService->generatePatientPdf($patient_id);

            if ($result['success']) {
                return response()->json([
                    'pdf_url' => $result['pdf_url'],
                    'data' => $result['data'],
                ]);
            } else {
                return response()->json([
                    'value' => false,
                    'message' => $result['message'],
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error while generating PDF: '.$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => __('api.error_occurred', ['message' => $e->getMessage()]),
            ], 500);
        }
    }

    public function patientFilterConditions()
    {
        try {
            $data = $this->questionService->getFilterConditions();

            if (empty($data)) {
                Log::info('No questions found for filter conditions.');

                return response()->json([
                    'value' => false,
                    'message' => 'No questions found.',
                ], 404);
            }

            $response = [
                'value' => true,
                'data' => $data,
            ];

            Log::info('Questions filter conditions retrieved successfully.', ['question_count' => count($data)]);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error('Error while fetching questions filter conditions: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.error_occurred', ['message' => $e->getMessage()]),
            ], 500);
        }
    }

    public function filteredPatients(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $onlyMyPatients = $request->boolean('only_my_patients', false);

            // Extract filter parameters (excluding pagination and scope parameters)
            $filterParams = $request->except(['page', 'per_page', 'sort', 'direction', 'offset', 'limit', 'only_my_patients']);

            // Cache the latest filter parameters for this user (for export functionality)
            // Duration: 2 hours - balances convenience with data freshness
            $userFilterCacheKey = 'latest_filter_params_user_'.auth()->id();
            Cache::put($userFilterCacheKey, $filterParams, now()->addHours(2));

            // Cache the scope parameter as well for export
            $userScopeCacheKey = 'latest_filter_scope_user_'.auth()->id();
            Cache::put($userScopeCacheKey, $onlyMyPatients, now()->addHours(2));

            $result = $this->patientFilterService->filterPatients(
                $request->all(),
                $perPage,
                $page,
                $onlyMyPatients
            );

            Log::info('Successfully retrieved filtered patients.', [
                'filter_count' => count($filterParams),
                'user_id' => auth()->id(),
                'only_my_patients' => $onlyMyPatients,
            ]);

            return response()->json([
                'value' => true,
                'data' => $result['data'],
                'pagination' => $result['pagination'],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving filtered patients.', ['exception' => $e]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to retrieve filtered patients.',
            ], 500);
        }
    }

    public function exportFilteredPatients()
    {
        try {
            // Get the latest filter parameters used by this user from cache
            $userFilterCacheKey = 'latest_filter_params_user_'.auth()->id();
            $filterParams = Cache::get($userFilterCacheKey, []);

            // Get the scope parameter from cache
            $userScopeCacheKey = 'latest_filter_scope_user_'.auth()->id();
            $onlyMyPatients = Cache::get($userScopeCacheKey, false);

            // Check if user has admin role when exporting all patients
            $user = auth()->user();
            if ($onlyMyPatients === false && ! $user->hasRole('Admin')) {
                return response()->json([
                    'value' => false,
                    'message' => 'Access denied. Admin role required to export all patients.',
                ], 403);
            }

            // If no cached filters found, return error
            if (empty($filterParams)) {
                return response()->json([
                    'value' => false,
                    'message' => 'No recent filter criteria found. Please apply filters first using the filteredPatients endpoint.',
                ], 400);
            }

            // Generate cache key from filter parameters and scope
            $cacheKey = 'filtered_patients_export_'.md5(json_encode($filterParams).'_'.$onlyMyPatients).'_'.auth()->id();

            // Cache the filter parameters for tracking
            Cache::put($cacheKey.'_filters', $filterParams, now()->addHours(2));
            Cache::put($cacheKey.'_scope', $onlyMyPatients, now()->addHours(2));

            Log::info('Starting filtered patients export with cached filters', [
                'user_id' => auth()->id(),
                'filter_count' => count($filterParams),
                'filter_params' => $filterParams,
                'only_my_patients' => $onlyMyPatients,
                'cache_key' => $cacheKey,
            ]);

            // Get all filtered patients (without pagination)
            $result = $this->patientFilterService->filterPatients($filterParams, PHP_INT_MAX, 1, $onlyMyPatients);
            $patients = $result['data'];

            if ($patients->isEmpty()) {
                return response()->json([
                    'value' => false,
                    'message' => 'No patients found matching the cached filter criteria.',
                ], 404);
            }

            // Get all questions for CSV headers (include 'type' to check for file questions)
            $questions = Cache::remember('all_questions_export', now()->addHour(), function () {
                return Questions::query()
                    ->select(['id', 'question', 'type'])
                    ->orderBy('id')
                    ->get();
            });

            // Pre-process patients: Index answers by question_id for O(1) lookup
            $processedPatients = collect($patients)->map(function ($patient) {
                // Ensure we have an array
                if (! is_array($patient)) {
                    $patient = is_object($patient) && method_exists($patient, 'toArray')
                        ? $patient->toArray()
                        : (array) $patient;
                }

                // Create an indexed array of answers by question_id
                $indexedAnswers = [];

                // Check if answers exists - could be array or Collection
                if (isset($patient['answers'])) {
                    $answers = $patient['answers'];

                    // Convert Collection to array if needed
                    if (is_object($answers) && method_exists($answers, 'toArray')) {
                        $answers = $answers->toArray();
                    } elseif (is_object($answers) && method_exists($answers, 'all')) {
                        $answers = $answers->all();
                    } elseif (! is_array($answers)) {
                        $answers = (array) $answers;
                    }

                    // Now iterate through answers
                    foreach ($answers as $answer) {
                        // Ensure answer is array
                        if (is_object($answer) && method_exists($answer, 'toArray')) {
                            $answer = $answer->toArray();
                        } elseif (! is_array($answer)) {
                            $answer = (array) $answer;
                        }

                        if (isset($answer['question_id'])) {
                            $indexedAnswers[$answer['question_id']] = $answer;
                        }
                    }
                }

                $patient['indexed_answers'] = $indexedAnswers;

                return $patient;
            });

            // Debug: Log first patient to check data structure
            if ($processedPatients->isNotEmpty()) {
                $firstPatient = $processedPatients->first();
                $indexedAnswers = $firstPatient['indexed_answers'] ?? [];
                Log::info('Export - First patient data check', [
                    'patient_id' => $firstPatient['id'] ?? 'unknown',
                    'has_answers' => isset($firstPatient['answers']),
                    'answers_type' => isset($firstPatient['answers']) ? gettype($firstPatient['answers']) : 'not set',
                    'has_indexed_answers' => isset($firstPatient['indexed_answers']),
                    'indexed_answers_count' => count($indexedAnswers),
                    'sample_indexed_keys' => array_slice(array_keys($indexedAnswers), 0, 10),
                    'sample_answer' => ! empty($indexedAnswers) ? array_values($indexedAnswers)[0] : 'no answers',
                    'questions_count' => $questions->count(),
                ]);
            }

            // Create the export class
            $export = new class($processedPatients, $questions, $filterParams) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping
            {
                private $patients;

                private $questions;

                private $filterParams;

                public function __construct($patients, $questions, $filterParams)
                {
                    $this->patients = $patients;
                    $this->questions = $questions;
                    $this->filterParams = $filterParams;
                }

                public function collection()
                {
                    return $this->patients;
                }

                public function headings(): array
                {
                    $headings = [
                        'Patient ID',
                        'Doctor ID',
                        'Doctor Name',
                        'Patient Name',
                        'Hospital',
                        'Submit Status',
                        'Outcome Status',
                        'Last Updated',
                    ];

                    // Add question headers
                    foreach ($this->questions as $question) {
                        $headings[] = $question->question;
                    }

                    return $headings;
                }

                public function map($patient): array
                {
                    // Ensure patient data is properly structured
                    if (is_object($patient) && method_exists($patient, 'toArray')) {
                        $patient = $patient->toArray();
                    } elseif (! is_array($patient)) {
                        $patient = (array) $patient;
                    }

                    $data = [
                        $patient['id'] ?? '',
                        $patient['doctor_id'] ?? '',
                        isset($patient['doctor']['name']) ? $patient['doctor']['name'] : '',
                        $patient['name'] ?? '',
                        $patient['hospital'] ?? '',
                        isset($patient['sections']['submit_status']) && $patient['sections']['submit_status'] ? 'Yes' : 'No',
                        isset($patient['sections']['outcome_status']) && $patient['sections']['outcome_status'] ? 'Yes' : 'No',
                        $patient['updated_at'] ?? '',
                    ];

                    // Use pre-indexed answers for O(1) lookup instead of nested loop
                    $indexedAnswers = $patient['indexed_answers'] ?? [];

                    foreach ($this->questions as $question) {
                        $answer = '';

                        // Direct lookup by question_id - O(1) instead of O(k)
                        if (isset($indexedAnswers[$question->id])) {
                            $patientAnswer = $indexedAnswers[$question->id];
                            $rawAnswer = $patientAnswer['answer'] ?? '';

                            // Special handling for file-type questions (Laboratory reports, etc.)
                            if ($question->type === 'files') {
                                $answer = $this->processFileAnswer($rawAnswer);
                            } else {
                                // Handle different answer types
                                if (is_array($rawAnswer)) {
                                    $answer = implode(', ', array_map('strval', $rawAnswer));
                                } elseif (is_string($rawAnswer)) {
                                    $answer = $rawAnswer;
                                } else {
                                    $answer = (string) $rawAnswer;
                                }

                                // Remove quotes if present
                                if (is_string($answer)) {
                                    $answer = trim($answer, '"');
                                }
                            }
                        }

                        $data[] = $answer;
                    }

                    return $data;
                }

                /**
                 * Process file-type answers and convert paths to URLs
                 * Based on the logic from patient_pdf2.blade.php
                 */
                private function processFileAnswer($filePaths): string
                {
                    if (empty($filePaths)) {
                        return '';
                    }

                    // If it's a JSON string, decode it
                    if (is_string($filePaths) && (str_starts_with($filePaths, '[') || str_starts_with($filePaths, '{'))) {
                        $decoded = json_decode($filePaths, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $filePaths = $decoded;
                        }
                    }

                    // If it's not an array, make it one
                    if (! is_array($filePaths)) {
                        $filePaths = [$filePaths];
                    }

                    // Convert each file path to full URL
                    $fileUrls = array_map(function ($filePath) {
                        // Remove quotes if present
                        $filePath = trim($filePath, '"');

                        // If the path is already a full URL, return it as-is
                        if (filter_var($filePath, FILTER_VALIDATE_URL)) {
                            return $filePath;
                        }

                        // Otherwise, convert storage path to URL
                        // Remove leading slashes and 'public/' prefix if present
                        $filePath = ltrim($filePath, '/');
                        $filePath = preg_replace('#^public/#', '', $filePath);

                        return url('storage/'.str_replace('\\/', '/', $filePath));
                    }, $filePaths);

                    // Return URLs joined with comma for Excel display
                    return implode(', ', $fileUrls);
                }
            };

            // Generate a unique filename with timestamp and filter info
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filterCount = count($filterParams);
            $filename = "filtered_patients_export_{$filterCount}_filters_{$timestamp}.xlsx";

            // Ensure the exports directory exists
            Storage::disk('public')->makeDirectory('exports');

            // Store the Excel file
            \Maatwebsite\Excel\Facades\Excel::store($export, 'exports/'.$filename, 'public');

            // Construct the full URL for the exported file
            $fileUrl = config('app.url').'/storage/exports/'.$filename;

            // Cache the export result for future reference
            Cache::put($cacheKey.'_result', [
                'filename' => $filename,
                'file_url' => $fileUrl,
                'patient_count' => $patients->count(),
                'created_at' => now()->toISOString(),
            ], now()->addHours(24));

            Log::info('Successfully exported filtered patients to CSV', [
                'file_url' => $fileUrl,
                'patient_count' => $patients->count(),
                'filter_count' => $filterCount,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'value' => true,
                'message' => 'Export completed successfully',
                'file_url' => $fileUrl,
                'patient_count' => $patients->count(),
                'filter_count' => $filterCount,
                'cache_key' => $cacheKey,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error exporting filtered patients to CSV: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'cached_filter_params' => Cache::get('latest_filter_params_user_'.auth()->id(), []),
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to export data: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark a patient
     */
    public function markPatient($patientId)
    {
        try {
            $result = $this->markedPatientService->addToMarked($patientId);

            if ($result['success']) {
                return response()->json([
                    'value' => true,
                    'message' => $result['message'],
                ], 200);
            }

            return response()->json([
                'value' => false,
                'message' => $result['message'],
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error marking patient: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'patient_id' => $patientId,
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to mark patient.',
            ], 500);
        }
    }

    /**
     * Unmark a patient
     */
    public function unmarkPatient($patientId)
    {
        try {
            $result = $this->markedPatientService->removeFromMarked($patientId);

            if ($result['success']) {
                return response()->json([
                    'value' => true,
                    'message' => $result['message'],
                ], 200);
            }

            return response()->json([
                'value' => false,
                'message' => $result['message'],
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error unmarking patient: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'patient_id' => $patientId,
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to unmark patient.',
            ], 500);
        }
    }

    /**
     * Get marked patients list
     * Matches format from App\Modules\Auth\Controllers\AuthController::doctorProfileGetPatients
     */
    public function getMarkedPatients(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);

            $result = $this->markedPatientService->getMarkedPatients($perPage);

            // Extract status_code and remove it from response
            $statusCode = $result['status_code'] ?? 200;
            unset($result['status_code']);

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error retrieving marked patients: '.$e->getMessage(), [
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to retrieve marked patients.',
            ], 500);
        }
    }
}
