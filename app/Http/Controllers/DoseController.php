<?php

namespace App\Http\Controllers;

use App\Models\Dose;
use Illuminate\Http\Request;
use App\Http\Requests\StoreDoseRequest;
use App\Http\Requests\UpdateDoseRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DoseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Retrieve all doses
            $doses = Dose::all();

            // Return success response with doses
            return response()->json([
                'value' => true,
                //'message' => 'Doses retrieved successfully',
                'doses' => $doses,
            ], 200);
        } catch (\Exception $e) {
            // Log any exceptions that occur
            Log::error('Exception occurred while retrieving doses.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'Failed to retrieve doses. Please try again later.',
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        try {
            // Optional: Return any necessary data for the create form
            return response()->json([
                'value' => true,
                'message' => 'Show create form',
            ], 200);
        } catch (\Exception $e) {
            // Log any exceptions that occur
            Log::error('Exception occurred while showing create form.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'Failed to show create form. Please try again later.',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreDoseRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreDoseRequest $request)
    {
        try {
            // Create a new dose using validated data
            $dose = Dose::create([
                'title' => $request->title,
                'description' => $request->description,
                'dose' => $request->dose,
            ]);

            // Check if dose creation was successful
            if (!$dose) {
                // Log an error if dose creation failed
                Log::error('Failed to create dose in store method.');

                return response()->json([
                    'value' => false,
                    'message' => 'Failed to create dose',
                ], 500);
            }

            // Log successful dose creation
            Log::info('Dose created successfully.', ['dose_id' => $dose->id]);

            // Return success response
            return response()->json([
                'value' => true,
                'message' => 'Dose created successfully',
                //'dose' => $dose, // Optionally return the created dose object
            ], 200);
        } catch (\Exception $e) {
            // Log any exceptions that occur
            Log::error('Exception occurred while storing dose.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'Failed to store dose. Please try again later.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $dose = Dose::findorfail($id);
            // Return specific dose
            return response()->json([
                'value' => true,
                //'message' => 'Dose retrieved successfully',
                'dose' => $dose,
            ], 200);
        } catch (\Exception $e) {
            // Log any exceptions that occur
            Log::error('Exception occurred while retrieving dose.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'Failed to retrieve dose. Please try again later.',
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Dose  $dose
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Dose $dose)
    {
        try {
            // Optional: Return any necessary data for the edit form
            return response()->json([
                'value' => true,
                'message' => 'Show edit form',
                'dose' => $dose,
            ], 200);
        } catch (\Exception $e) {
            // Log any exceptions that occur
            Log::error('Exception occurred while showing edit form.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'Failed to show edit form. Please try again later.',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDoseRequest $request, $id)
    {
        $dose = Dose::where('id', $id)->first();

        try {
            if ($dose != null) {
                $dose->update($request->all());
                $response = [
                    'value' => true,
                    'data' => $dose,
                    'message' => 'Dose Updated Successfully',
                ];

                // Log successful dose update
                Log::info('Dose updated successfully.', ['dose_id' => $id]);

                return response($response, 201);
            } else {
                $response = [
                    'value' => false,
                    'message' => 'No Dose was found',
                ];

                return response($response, 404);
            }
        } catch (\Exception $e) {
            // Log any exceptions that occur
            Log::error('Exception occurred while updating dose.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            // Return error response
            return response()->json([
                'success' => false,
                'message' => 'Failed to update dose. Please try again later.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $dose = Dose::findOrFail($id);

            if ($dose != null) {
                // Delete the dose
                $dose->delete();

                // Log successful dose deletion
                Log::info('Dose deleted successfully.', ['dose_id' => $id]);

                $response = [
                    'value' => true,
                    'message' => 'Contact Deleted Successfully',
                ];

                return response($response, 201);
            } else {
                $response = [
                    'value' => false,
                    'message' => 'No Contact was found',
                ];

                return response($response, 404);
            }
        } catch (\Exception $e) {
            // Log any exceptions that occur
            Log::error('Exception occurred while deleting dose.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'Failed to delete dose. Please try again later.',
            ], 500);
        }
    }
}
