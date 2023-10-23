<?php

namespace App\Http\Controllers;

use App\Models\{Assessment, Cause, Complaint, Examination, PatientHistory, Risk, Section, User, Score, ScoreHistory,Decision,Outcome};
use App\Http\Requests\StorePatientHistoryRequest;
use App\Http\Requests\UpdatePatientHistoryRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PatientHistoryController extends Controller
{
    protected $patientHistory;
    protected $section;
    protected $complaint;
    protected $cause;
    protected $risk;
    protected $assessment;
    protected $examination;

    public function __construct(
        PatientHistory $patientHistory,
        Section $section,
        Complaint $complaint,
        Cause $cause,
        Risk $risk,
        Assessment $assessment,
        Examination $examination,
        Decision $decision,
        Outcome $outcome
    ) {
        $this->patientHistory = $patientHistory;
        $this->section = $section;
        $this->complaint = $complaint;
        $this->cause = $cause;
        $this->risk = $risk;
        $this->assessment = $assessment;
        $this->examination = $examination;
        $this->decision = $decision;
        $this->outcome = $outcome;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Patient = PatientHistory::with('doctor:id,name,lname')
                            ->with(['sections' => function ($query){
                                $query->select('patient_id','submit_status', 'outcome_status');
                            }])
                            ->latest('updated_at')
                            ->get();
                            //->paginate(10);

        if($Patient->isNotEmpty()){
            $response = [
                'value' => true,
                'data' => $Patient
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Patient was found'
            ];
            return response($response, 404);
        }

    }

        /**
     * Display a listing of the resource.
     */
    public function doctorPatientGetAll()
    {
        $Patient = PatientHistory::with('doctor:id,name,lname')
                                    ->with(['sections' => function ($query){
                                        $query->select('patient_id','submit_status', 'outcome_status');
                                    }])
                                    ->latest('updated_at')
                                    ->get(['id','doctor_id','name','hospital','updated_at']);
                                   // ->paginate(10,['id','doctor_id','name','hospital','created_at','updated_at']);
        if($Patient->isNotEmpty()){
            $response = [
                'value' => true,
                'data' => $Patient
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Patient was found'
            ];
            return response($response, 404);
        }

    }

    public function doctorPatientGet()
    {
        /*$Patient = $user->patients()
                            ->latest()
                            ->paginate(10,['id','doctor_id','name','hospital','created_at','updated_at']);*/

        $user = Auth::user();
        /** @var TYPE_NAME $Patient */
        $Patient = $user->patients()
                        //->with('sections:patient_id,submit_status,outcome_status')
                        ->with('doctor:id,name,lname')
                        ->with(['sections' => function ($query){
                            $query->select('patient_id','submit_status', 'outcome_status');
                        }])
                        ->latest('updated_at')
                        ->get(['id','doctor_id','name','hospital','updated_at']);

        $patientCount = $user->patients->count();
        if($patientCount!=null){
            $count = $patientCount;
        }else{
            $count = 0;
        }

        $scoreValue  = $user->score->score;
        if($scoreValue !=null){
            $score = $scoreValue;
        }else{
            $score = 0;
        }
        if($Patient->isNotEmpty()){
            $response = [
                'value' => true,
                'patient_count' => $count,
                'score_value' => $score,
                'data' => $Patient,
                //'sections' => $sections
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Patient was found'
            ];
            return response($response, 404);
        }
    }

    //@param \Illuminate\Http\Request $request
   // @return \Illuminate\Http\Response
    public function store(StorePatientHistoryRequest $request)
    {
        try {
            $patient = DB::transaction(function () use ($request) {
                $patient = $this->patientHistory->create($request->all());
    
                $relatedData = [
                    'doctor_id' => $request['doctor_id'],
                    'patient_id' => $patient->id,
                ];
    
                $this->section->create(array_merge($relatedData, ['section_1' => true]));
                $this->complaint->create($relatedData);
                $this->cause->create($relatedData);
                $this->risk->create($relatedData);
                $this->assessment->create($relatedData);
                $this->examination->create($relatedData);
                $this->decision->create($relatedData);
                $this->outcome->create($relatedData);

                //scoring system
                $doctorId = auth()->user()->id; // Assuming you have authentication in place
                $score = Score::where('doctor_id', $doctorId)->first();
                
                $incrementAmount = 10; // Example increment amount
                $action = 'Add new Patient'; // Example action
                
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
                ]);

                return $patient;
            });
    
            $response = [
                'value' => true,
                'data' => $patient,
            ];
    
            return response($response, 200);
        } catch (\Exception $e) {
            $response = [
                'value' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
    
            return response($response, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $Patient = PatientHistory::find($id);

        if($Patient!=null){
            $response = [
                'value' => true,
                'data' => $Patient
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Patient was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePatientHistoryRequest $request, $id)
    {
        $Patient = PatientHistory::find($id);

        if($Patient!=null){
           // $Patient->update($request->all());
            $questionMap =$request->all();
            if ($request->has('1')) {
                PatientHistory::where('id', $id)->update(['name' => $request->input('1')]);
            }
            
            if ($request->has('2')) {
                PatientHistory::where('id', $id)->update(['hospital' => $request->input('2')]);
            }

            if ($request->has('3')) {
                PatientHistory::where('id', $id)->update(['collected_data_from' => $request->input('3')]);
            }
            
            if ($request->has('4')) {
                PatientHistory::where('id', $id)->update(['NID' => $request->input('4')]);
            }
            
            if ($request->has('5')) {
                PatientHistory::where('id', $id)->update(['phone' => $request->input('5')]);
            }
            
            if ($request->has('6')) {
                PatientHistory::where('id', $id)->update(['email' => $request->input('6')]);
            }
            
            if ($request->has('7')) {
                PatientHistory::where('id', $id)->update(['age' => $request->input('7')]);
            }
            
            if ($request->has('8')) {
                PatientHistory::where('id', $id)->update(['gender' => $request->input('8')]);
            }
            
            if ($request->has('9')) {
                PatientHistory::where('id', $id)->update(['occupation' => $request->input('9')]);
            }
            
            if ($request->has('10')) {
                PatientHistory::where('id', $id)->update(['residency' => $request->input('10')]);
            }
            
            if ($request->has('11')) {
                PatientHistory::where('id', $id)->update(['governorate' => $request->input('11')]);
            }
            
            if ($request->has('12')) {
                PatientHistory::where('id', $id)->update(['marital_status' => $request->input('12')]);
            }
            
            if ($request->has('13')) {
                PatientHistory::where('id', $id)->update(['educational_level' => $request->input('13')]);
            }
            
            if ($request->has('14')) {
                PatientHistory::where('id', $id)->update(['special_habits_of_the_patient' => $request->input('14')]);
            }
            
            if ($request->has('15')) {
                PatientHistory::where('id', $id)->update(['DM' => $request->input('15')]);
            }
            
            if ($request->has('16')) {
                PatientHistory::where('id', $id)->update(['DM_duration' => $request->input('16')]);
            }
            
            if ($request->has('17')) {
                PatientHistory::where('id', $id)->update(['HTN' => $request->input('17')]);
            }
            
            if ($request->has('18')) {
                PatientHistory::where('id', $id)->update(['HTN_duration' => $request->input('18')]);
            }
            
            if ($request->has('19')) {
                PatientHistory::where('id', $id)->update(['other' => $request->input('19')]);
            }
            $response = [
                'value' => true,
                'map' => $questionMap,
                'message' => 'Patient Updated Successfully'
            ];
            return response()->json($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Patient was found'
            ];
            return response()->json($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Patient = PatientHistory::find($id);

        if($Patient!=null){
            PatientHistory::destroy($id);
            DB::table('sections')->where('patient_id', '=', $id)->delete();
            DB::table('complaints')->where('patient_id', '=', $id)->delete();
            DB::table('causes')->where('patient_id', '=', $id)->delete();
            DB::table('risks')->where('patient_id', '=', $id)->delete();
            DB::table('assessments')->where('patient_id', '=', $id)->delete();
            DB::table('examinations')->where('patient_id', '=', $id)->delete();
            DB::table('comments')->where('patient_id', '=', $id)->delete();
            DB::table('decisions')->where('patient_id', '=', $id)->delete();
            DB::table('outcomes')->where('patient_id', '=', $id)->delete();

            $response = [
                'value' => true,
                'message' => 'Patient Deleted Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Patient was found'
            ];
            return response($response, 404);
        }
    }

         /**
     * Search for product by name
     * @param str $name
     * @return \Illuminate\Http\Response
     */
    public function search($name)
    {
        $Patient = PatientHistory::where('name','like','%'.$name.'%')
                                    ->orWhere('hospital','like','%'.$name.'%')
                                    ->with('doctor:id,name,lname')
                                    ->with(['sections' => function ($query){
                                        $query->select('patient_id','submit_status', 'outcome_status');
                                    }])
                                    ->latest('updated_at')
                                    ->get(['id','doctor_id','name','hospital','updated_at']);

        if($Patient!=null){
            $response = [
                'value' => true,
                'data' => $Patient
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Patient was found'
            ];
            return response($response, 404);
        }
    }
}
