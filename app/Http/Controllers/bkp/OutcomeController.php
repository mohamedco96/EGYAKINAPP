<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOutcomeRequest;
use App\Http\Requests\UpdateOutcomeRequest;
use App\Models\AppNotification;
use App\Models\Outcome;
use App\Models\PatientHistory;
use App\Models\Score;
use App\Models\ScoreHistory;
use App\Notifications\ReachingSpecificPoints;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OutcomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$Outcome = Outcome::latest()->paginate(10);
        $Outcome = Outcome::latest()->get();

        if ($Outcome->isNotEmpty()) {
            $response = [
                'value' => true,
                'data' => $Outcome,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Outcome was found',
            ];

            return response($response, 404);
        }
    }

    //@param \Illuminate\Http\Request $request
    // @return \Illuminate\Http\Response
    public function store(StoreOutcomeRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $doctorId = Auth::id();
            $patientId = $request->patient_id;

            // Create the Outcome record
            $outcome = Outcome::create([
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'outcome_of_the_patient' => $request->outcome_of_the_patient,
                'creatinine_on_discharge' => $request->creatinine_on_discharge,
                'duration_of_admission' => $request->duration_of_admission,
                'final_status' => $request->final_status,
                'other' => $request->other,
            ]);

            // Update the sections table
            DB::table('sections')->where('patient_id', $patientId)->update(['outcome_status' => true]);


            // Scoring system
            $incrementAmount = 1;
            $action = 'Add Outcome';

            $score = Score::firstOrNew(['doctor_id' => $doctorId]);
            $score->score += $incrementAmount;
            $score->threshold += $incrementAmount;
            $newThreshold = $score->threshold;

            // Send notification if the new score exceeds 50 or its multiples
            if ($newThreshold >= 50) {
                // Load user object
                $user = Auth::user();
                // Send notification
                $user->notify(new ReachingSpecificPoints($score));
                $score->threshold = 0;
            }

            $score->save();

            // Log score history
            ScoreHistory::create([
                'doctor_id' => $doctorId,
                'score' => $incrementAmount,
                'action' => $action,
                'timestamp' => now(),
            ]);


            // Send notification if necessary
            $patientDoctor = PatientHistory::where('id', $patientId)->first();
            $doctorId = ($patientDoctor->doctor_id == $doctorId) ? 'No need to send notification' : $patientDoctor->doctor_id;
            if ($doctorId != 'No need to send notification') {
                AppNotification::create([
                    'content' => 'Outcome was created',
                    'read' => false,
                    'type' => 'Outcome',
                    'patient_id' => $patientId,
                    'doctor_id' => $doctorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Return response
            if ($outcome) {
                $response = [
                    'value' => true,
                    'message' => 'Outcome Created Successfully',
                ];

                return response($response, 200);
            } else {
                $response = [
                    'value' => false,
                    'message' => 'No Outcome was found',
                ];

                return response($response, 404);
            }
        });
    }

    /**
     * Display the specified resource.
     */
    public function show($patient_id)
    {
        $Outcome = Outcome::where('patient_id', $patient_id)
            ->select('doctor_id', 'outcome_of_the_patient', 'creatinine_on_discharge','duration_of_admission', 'final_status', 'other', 'updated_at')
            ->with('doctor:id,name,lname')
            ->first();

        $data = [
            'first_name' => $Outcome->name,
            'last_name' => $Outcome->lname,
        ];

        if ($Outcome != null) {
            $response = [
                'value' => true,
                'data' => $Outcome,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Outcome was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOutcomeRequest $request, $id)
    {
        $Outcome = Outcome::where('patient_id', $id)->first();

        if ($Outcome != null) {
            $Outcome->update($request->all());
            $response = [
                'value' => true,
                'data' => $Outcome,
                'message' => 'Outcome Updated Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Outcome was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Outcome = Outcome::where('patient_id', $id)->first();

        if ($Outcome != null) {
            DB::table('Outcomes')->where('patient_id', $id)->delete();
            $response = [
                'value' => true,
                'message' => 'Outcome Deleted Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Outcome was found',
            ];

            return response($response, 404);
        }
    }
}
