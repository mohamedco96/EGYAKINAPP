<?php

namespace App\Http\Controllers;

use App\Events\SearchResultsUpdated;
use App\Http\Requests\UpdatePatientsRequest;
use App\Services\PatientService;
use App\Services\HomeDataService;
use App\Services\SearchService;
use App\Services\QuestionService;
use App\Services\FileUploadService;
use App\Services\PatientFilterService;
use App\Services\PdfGenerationService;
use App\Models\Questions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PatientsController extends Controller
{
    protected $patientService;
    protected $homeDataService;
    protected $searchService;
    protected $questionService;
    protected $fileUploadService;
    protected $patientFilterService;
    protected $pdfGenerationService;

    public function __construct(
        PatientService $patientService,
        HomeDataService $homeDataService,
        SearchService $searchService,
        QuestionService $questionService,
        FileUploadService $fileUploadService,
        PatientFilterService $patientFilterService,
        PdfGenerationService $pdfGenerationService
    ) {
        $this->patientService = $patientService;
        $this->homeDataService = $homeDataService;
        $this->searchService = $searchService;
        $this->questionService = $questionService;
        $this->fileUploadService = $fileUploadService;
        $this->patientFilterService = $patientFilterService;
        $this->pdfGenerationService = $pdfGenerationService;
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
                'trace' => $e->getTraceAsString()
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
            $paginatedPatients = $this->patientFilterService->getDoctorPatients(true);
            $filterConditions = $this->questionService->getFilterConditions();

            $response = [
                'value' => true,
                'filter' => $filterConditions,
                'data' => $paginatedPatients,
            ];

            Log::info('Successfully retrieved all patients for doctor.', [
                'doctor_id' => optional(auth()->user())->id
            ]);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving all patients for doctor.', [
                'doctor_id' => optional(auth()->user())->id, 
                'exception' => $e
            ]);

            return response()->json(['error' => 'Failed to retrieve all patients for doctor.'], 500);
        }
    }

    public function doctorPatientGet()
    {
        try {
            $paginatedPatients = $this->patientFilterService->getDoctorPatients(false);
            
            $user = auth()->user();
            $userPatientCount = $user->patients()->count();
            $scoreValue = optional($user->score)->score ?? 0;
            $isVerified = (bool)$user->email_verified_at;

            $response = [
                'value' => true,
                'verified' => $isVerified,
                'patient_count' => strval($userPatientCount),
                'score_value' => strval($scoreValue),
                'data' => $paginatedPatients,
            ];

            Log::info('Successfully retrieved current doctor patients.', [
                'doctor_id' => optional(auth()->user())->id
            ]);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving current doctor patients.', [
                'doctor_id' => optional(auth()->user())->id, 
                'exception' => $e
            ]);

            return response()->json(['error' => 'Failed to retrieve current doctor patients.'], 500);
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
                'doctor_id' => optional(auth()->user())->id
            ]);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving doctor profile patients.', [
                'doctor_id' => optional(auth()->user())->id, 
                'exception' => $e
            ]);

            return response()->json(['error' => 'Failed to retrieve doctor profile patients.'], 500);
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
            Log::error("Error while storing patient: " . $e->getMessage(), [
                'request' => $request->all()
            ]);
            
            return response()->json([
                'value' => false,
                'message' => 'Error: ' . $e->getMessage(),
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
        if (!$sectionExists) {
            return response()->json([
                'value' => false,
                'message' => "Section not found",
            ], 404);
        }

        if (!$this->patientService->patientExists($patient_id)) {
            return response()->json([
                'value' => false,
                'message' => "Patient not found",
            ], 404);
        }

        try {
            $response = $this->patientService->updatePatientSection($request->all(), $section_id, $patient_id);
            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error("Error while updating patient: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'Error: ' . $e->getMessage(),
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
                'message' => 'Error occurred while deleting patient: ' . $e->getMessage(),
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
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'value' => false,
                    'message' => $result['message'],
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error("Error while generating PDF: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function patientFilterConditions()
    {
        try {
            $data = $this->questionService->getFilterConditions();

            if (empty($data)) {
                Log::info("No questions found for filter conditions.");
                return response()->json([
                    'value' => false,
                    'message' => 'No questions found.',
                ], 404);
            }

            $response = [
                'value' => true,
                'data' => $data,
            ];

            Log::info("Questions filter conditions retrieved successfully.", ['question_count' => count($data)]);
            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error("Error while fetching questions filter conditions: " . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function filteredPatients(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $result = $this->patientFilterService->filterPatients($request->all(), $perPage, $page);

            Log::info('Successfully retrieved filtered patients.', [
                'filter_count' => count($request->except(['page', 'per_page', 'sort', 'direction', 'offset', 'limit']))
            ]);

            return response()->json([
                'value' => true,
                'data' => $result['data'],
                'pagination' => $result['pagination']
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving filtered patients.', ['exception' => $e]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to retrieve filtered patients.',
            ], 500);
        }
    }
}
