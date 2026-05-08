<?php

namespace App\Modules\Doses\Services;

use App\Modules\Doses\Models\Dose;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class DoseService
{
    /**
     * Get all doses
     */
    public function getAllDoses(): array
    {
        try {
            $doses = Dose::all();

            return [
                'success' => true,
                'data' => $doses,
                'message' => 'Doses retrieved successfully',
            ];
        } catch (Exception $e) {
            Log::error('Exception occurred while retrieving doses.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve doses. Please try again later.',
            ];
        }
    }

    /**
     * Create a new dose
     */
    public function createDose(array $data): array
    {
        try {
            $dose = Dose::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'dose' => $data['dose'],
            ]);

            if (! $dose) {
                Log::error('Failed to create dose in service.');

                return [
                    'success' => false,
                    'message' => 'Failed to create dose',
                ];
            }

            Log::info('Dose created successfully.', ['dose_id' => $dose->id]);

            return [
                'success' => true,
                'data' => $dose,
                'message' => 'Dose created successfully',
            ];
        } catch (Exception $e) {
            Log::error('Exception occurred while storing dose.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to store dose. Please try again later.',
            ];
        }
    }

    /**
     * Get a specific dose by ID
     */
    public function getDoseById(int $id): array
    {
        try {
            $dose = Dose::findOrFail($id);

            return [
                'success' => true,
                'data' => $dose,
                'message' => 'Dose retrieved successfully',
            ];
        } catch (ModelNotFoundException $e) {
            Log::warning('Dose not found.', ['dose_id' => $id]);

            return [
                'success' => false,
                'message' => 'No dose was found',
                'status_code' => 404,
            ];
        } catch (Exception $e) {
            Log::error('Exception occurred while retrieving dose.', [
                'dose_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve dose. Please try again later.',
            ];
        }
    }

    /**
     * Update a dose
     */
    public function updateDose(int $id, array $data): array
    {
        try {
            $dose = Dose::find($id);

            if (! $dose) {
                return [
                    'success' => false,
                    'message' => 'No dose was found',
                    'status_code' => 404,
                ];
            }

            $dose->update($data);

            Log::info('Dose updated successfully.', ['dose_id' => $id]);

            return [
                'success' => true,
                'data' => $dose->fresh(),
                'message' => 'Dose updated successfully',
            ];
        } catch (Exception $e) {
            Log::error('Exception occurred while updating dose.', [
                'dose_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update dose. Please try again later.',
            ];
        }
    }

    /**
     * Delete a dose
     */
    public function deleteDose(int $id): array
    {
        try {
            $dose = Dose::find($id);

            if (! $dose) {
                return [
                    'success' => false,
                    'message' => 'No dose was found',
                    'status_code' => 404,
                ];
            }

            $dose->delete();

            Log::info('Dose deleted successfully.', ['dose_id' => $id]);

            return [
                'success' => true,
                'message' => 'Dose deleted successfully',
            ];
        } catch (Exception $e) {
            Log::error('Exception occurred while deleting dose.', [
                'dose_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to delete dose. Please try again later.',
            ];
        }
    }

    /**
     * Search doses with pagination
     */
    public function searchDoses(string $query, int $perPage = 10): array
    {
        try {
            // Validate that query is not empty
            if (empty(trim($query))) {
                return [
                    'success' => false,
                    'message' => 'Search query cannot be empty',
                    'status_code' => 400,
                ];
            }

            // Decode the query parameter in case it's URL encoded
            $query = urldecode($query);

            Log::info('Starting dose search', ['query' => $query, 'per_page' => $perPage]);

            $doses = Dose::where('title', 'like', "%$query%")->paginate($perPage);

            Log::info('Dose search completed', [
                'query' => $query,
                'results_count' => $doses->total(),
                'current_page' => $doses->currentPage(),
                'per_page' => $doses->perPage(),
            ]);

            return [
                'success' => true,
                'data' => $doses,
                'message' => 'Doses retrieved successfully',
            ];
        } catch (Exception $e) {
            Log::error('Exception occurred while searching for doses.', [
                'query' => $query ?? 'N/A',
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to search for doses. Please try again later.',
            ];
        }
    }
}
