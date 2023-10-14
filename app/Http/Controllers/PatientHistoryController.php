<?php

namespace App\Http\Controllers;

use App\Models\{Assessment, Cause, Complaint, Examination, PatientHistory, Risk, Section, User};
use App\Http\Requests\StorePatientHistoryRequest;
use App\Http\Requests\UpdatePatientHistoryRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        Examination $examination
    ) {
        $this->patientHistory = $patientHistory;
        $this->section = $section;
        $this->complaint = $complaint;
        $this->cause = $cause;
        $this->risk = $risk;
        $this->assessment = $assessment;
        $this->examination = $examination;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Patient = PatientHistory::latest()
                            ->with('owner:id,name,lname')
                            ->with('sections')
                            ->get();
                            //->paginate(10);

        if($Patient!=null){
            $response = [
                'value' => true,
                'data' => $Patient
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false
            ];
            return response($response, 404);
        }

    }

        /**
     * Display a listing of the resource.
     */
    public function doctorPatientGetAll()
    {
        $Patient = PatientHistory::with('owner:id,name,lname')
                                    ->with('sections')
                                    ->latest()
                                    ->get(['id','owner_id','name','hospital','created_at','updated_at']);
                                   // ->paginate(10,['id','owner_id','name','hospital','created_at','updated_at']);
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

    public function doctorPatientGet()
    {
        /*$Patient = $user->patients()
                            ->latest()
                            ->paginate(10,['id','owner_id','name','hospital','created_at','updated_at']);*/

        $user = Auth::user();
        /** @var TYPE_NAME $Patient */
        $Patient = $user->patients()
                        //->with('sections:patient_id,submit_status,outcome_status')
                        ->with('owner:id,name,lname')
                        ->with('sections')
                        ->latest()
                        ->get(['id','owner_id','name','hospital','created_at','updated_at']);

        if($Patient!=null){
            $response = [
                'value' => true,
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
                    'owner_id' => $request['owner_id'],
                    'patient_id' => $patient->id,
                ];
    
                $this->section->create(array_merge($relatedData, ['section_1' => true]));
                $this->complaint->create($relatedData);
                $this->cause->create($relatedData);
                $this->risk->create($relatedData);
                $this->assessment->create($relatedData);
                $this->examination->create($relatedData);
    
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
            $Patient->update($request->all());
            $response = [
                'value' => true,
                'data' => $Patient,
                'message' => 'Patient Updated Successfully'
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
                                    ->with('owner:id,name,lname')
                                    ->with('sections')
                                    ->latest()
                                    ->get(['id','owner_id','name','hospital','created_at','updated_at']);

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
