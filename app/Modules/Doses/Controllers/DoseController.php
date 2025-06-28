<?php

namespace App\Modules\Doses\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Doses\Models\Dose;
use App\Modules\Doses\Requests\StoreDoseRequest;
use App\Modules\Doses\Requests\UpdateDoseRequest;
use App\Modules\Doses\Resources\DoseApiResource;
use App\Modules\Doses\Services\DoseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoseController extends Controller
{
    protected DoseService $doseService;

    public function __construct(DoseService $doseService)
    {
        $this->doseService = $doseService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $result = $this->doseService->getAllDoses();

        if (!$result['success']) {
            return response()->json([
                'value' => false,
                'message' => $result['message'],
            ], 500);
        }

        return response()->json([
            'value' => true,
            'doses' => DoseApiResource::collection($result['data']),
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        return response()->json([
            'value' => true,
            'message' => 'Show create form',
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreDoseRequest $request
     * @return JsonResponse
     */
    public function store(StoreDoseRequest $request): JsonResponse
    {
        $result = $this->doseService->createDose($request->validated());

        if (!$result['success']) {
            return response()->json([
                'value' => false,
                'message' => $result['message'],
            ], 500);
        }

        return response()->json([
            'value' => true,
            'data' => new DoseApiResource($result['data']),
            'message' => $result['message'],
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->doseService->getDoseById($id);

        if (!$result['success']) {
            $statusCode = $result['status_code'] ?? 500;
            return response()->json([
                'value' => false,
                'message' => $result['message'],
            ], $statusCode);
        }

        return response()->json([
            'value' => true,
            'dose' => new DoseApiResource($result['data']),
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Dose $dose
     * @return JsonResponse
     */
    public function edit(Dose $dose): JsonResponse
    {
        return response()->json([
            'value' => true,
            'message' => 'Show edit form',
            'dose' => new DoseApiResource($dose),
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateDoseRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateDoseRequest $request, int $id): JsonResponse
    {
        $result = $this->doseService->updateDose($id, $request->validated());

        if (!$result['success']) {
            $statusCode = $result['status_code'] ?? 500;
            return response()->json([
                'value' => false,
                'message' => $result['message'],
            ], $statusCode);
        }

        return response()->json([
            'value' => true,
            'data' => new DoseApiResource($result['data']),
            'message' => $result['message'],
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->doseService->deleteDose($id);

        if (!$result['success']) {
            $statusCode = $result['status_code'] ?? 500;
            return response()->json([
                'value' => false,
                'message' => $result['message'],
            ], $statusCode);
        }

        return response()->json([
            'value' => true,
            'message' => 'Contact Deleted Successfully', // Keeping original message for API compatibility
        ], 201);
    }

    /**
     * Search doses with pagination
     *
     * @param Request $request
     * @param string $query
     * @return JsonResponse
     */
    public function doseSearch(Request $request, string $query): JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $result = $this->doseService->searchDoses($query, $perPage);

        if (!$result['success']) {
            $statusCode = $result['status_code'] ?? 500;
            return response()->json([
                'value' => false,
                'message' => $result['message'],
            ], $statusCode);
        }

        return response()->json([
            'value' => true,
            'data' => $result['data'], // Keeping paginated structure intact
            'message' => $result['message'],
        ]);
    }
}
