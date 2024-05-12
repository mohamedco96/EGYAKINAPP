<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExaminationRequest;
use App\Http\Requests\UpdateExaminationRequest;
use App\Models\Examination;
use Illuminate\Support\Facades\DB;

class ExaminationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$Examination = Examination::latest()->paginate(10);
        $Examination = Examination::latest()->get();

        if ($Examination != null) {
            $response = [
                'value' => true,
                'data' => $Examination,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
            ];

            return response($response, 404);
        }
    }

    //@param \Illuminate\Http\Request $request
    // @return \Illuminate\Http\Response
    public function store(StoreExaminationRequest $request)
    {
        $Examination = Examination::create($request->all());

        if ($Examination != null) {
            $response = [
                'value' => true,
                'data' => $Examination,
            ];

            return response($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Examination was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $Examination = Examination::where('patient_id', $id)->first();

        if ($Examination != null) {
            $response = [
                'value' => true,
                'data' => $Examination,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Examination was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExaminationRequest $request, $id)
    {
        $Examination = Examination::where('patient_id', $id)->first();

        if ($Examination != null) {
            $Examination->update($request->all());

            DB::table('sections')->where('patient_id', $id)->update(['section_6' => true]);
            /*
            //scoring system
            $doctorId = auth()->user()->id; // Assuming you have authentication in place
            $score = Score::where('doctor_id', $doctorId)->first();

            $incrementAmount = 5; // Example increment amount
            $action = 'Update Laboratory and radiology results Section'; // Example action

            if ($score) {
                $score->increment('score', $incrementAmount); // Increase the score
            } else {
                Score::create([
                    'doctor_id' => $doctorId,
                    'score' => $incrementAmount,
                ]);
            }

            ScoreHistory::create([
                'doctor_id' => $doctorId,
                'score' => $incrementAmount,
                'action' => $action,
                'timestamp' => now(),
            ]);*/

            $response = [
                'value' => true,
                'data' => $Examination,
                'message' => 'Examination Updated Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Examination was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Examination = Examination::where('patient_id', $id)->first();

        if ($Examination != null) {
            DB::table('examinations')->where('patient_id', $id)->delete();

            $response = [
                'value' => true,
                'message' => 'Examination Deleted Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Examination was found',
            ];

            return response($response, 404);
        }
    }
}
