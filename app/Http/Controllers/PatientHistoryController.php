<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientHistoryRequest;
use App\Http\Requests\UpdatePatientHistoryRequest;
use App\Models\Assessment;
use App\Models\Cause;
use App\Models\Complaint;
use App\Models\Decision;
use App\Models\Examination;
use App\Models\Notification;
use App\Models\Outcome;
use App\Models\PatientHistory;
use App\Models\Risk;
use App\Models\Score;
use App\Models\ScoreHistory;
use App\Models\Section;
use App\Models\User;
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
            ->with(['sections' => function ($query) {
                $query->select('patient_id', 'submit_status', 'outcome_status');
            }])
            ->latest('updated_at')
            ->get();
        //->paginate(10);

        if ($Patient->isNotEmpty()) {
            $response = [
                'value' => true,
                'data' => $Patient,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Patient was found',
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
            ->with(['sections' => function ($query) {
                $query->select('patient_id', 'submit_status', 'outcome_status');
            }])
            ->latest('updated_at')
            ->get(['id', 'doctor_id', 'name', 'hospital', 'updated_at']);
        // ->paginate(10,['id','doctor_id','name','hospital','created_at','updated_at']);
        if ($Patient->isNotEmpty()) {
            $response = [
                'value' => true,
                'data' => $Patient,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Patient was found',
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
            ->with(['sections' => function ($query) {
                $query->select('patient_id', 'submit_status', 'outcome_status');
            }])
            ->latest('updated_at')
            ->get(['id', 'doctor_id', 'name', 'hospital', 'updated_at']);

        $count = 0;
        if ($user->patients && $user->patients->count() !== null) {
            $count = $user->patients->count();
        }

        $score = 0;
        if ($user->score && $user->score->score !== null) {
            $score = $user->score->score;
        }

        if ($Patient->isNotEmpty()) {
            $response = [
                'value' => true,
                'patient_count' => $count,
                'score_value' => $score,
                'data' => $Patient,
                //'sections' => $sections
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Patient was found',
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

                $doctor_id = Auth::id();
                $questionMap = $request->all();
                if ($request->has('1')) {
                    $name = $request->input('1');
                } else {
                    $name = null;
                }
                if ($request->has('2')) {
                    $hospital = $request->input('2');
                } else {
                    $hospital = null;
                }
                if ($request->has('3')) {
                    $collected_data_from = $request->input('3');
                } else {
                    $collected_data_from = null;
                }
                if ($request->has('4')) {
                    $NID = $request->input('4');
                } else {
                    $NID = null;
                }
                if ($request->has('5')) {
                    $phone = $request->input('5');
                } else {
                    $phone = null;
                }
                if ($request->has('6')) {
                    $email = $request->input('6');
                } else {
                    $email = null;
                }
                if ($request->has('7')) {
                    $age = $request->input('7');
                } else {
                    $age = null;
                }
                if ($request->has('8')) {
                    $gender = $request->input('8');
                } else {
                    $gender = null;
                }
                if ($request->has('9')) {
                    $occupation = $request->input('9');
                } else {
                    $occupation = null;
                }
                if ($request->has('10')) {
                    $residency = $request->input('10');
                } else {
                    $residency = null;
                }
                if ($request->has('11')) {
                    $governorate = $request->input('11');
                } else {
                    $governorate = null;
                }
                if ($request->has('12')) {
                    $marital_status = $request->input('12');
                } else {
                    $marital_status = null;
                }
                if ($request->has('13')) {
                    $educational_level = $request->input('13');
                } else {
                    $educational_level = null;
                }
                if ($request->has('14')) {
                    $special_habits_of_the_patient = $request->input('14.answer');
                    $other_habits_of_the_patient = $request->input('14.other_field');
                } else {
                    $special_habits_of_the_patient = null;
                    $other_habits_of_the_patient = null;
                }
                if ($request->has('16')) {
                    $DM = $request->input('16');
                } else {
                    $DM = null;
                }
                if ($request->has('17')) {
                    $DM_duration = $request->input('17');
                } else {
                    $DM_duration = null;
                }
                if ($request->has('18')) {
                    $HTN = $request->input('18');
                } else {
                    $HTN = null;
                }
                if ($request->has('19')) {
                    $HTN_duration = $request->input('19');
                } else {
                    $HTN_duration = null;
                }
                if ($request->has('20')) {
                    $other = $request->input('20');
                } else {
                    $other = null;
                }

                $patient = $this->patientHistory->create([
                    'doctor_id' => $doctor_id,
                    'name' => $name,
                    'hospital' => $hospital,
                    'collected_data_from' => $collected_data_from,
                    'NID' => $NID,
                    'phone' => $phone,
                    'email' => $email,
                    'age' => $age,
                    'gender' => $gender,
                    'occupation' => $occupation,
                    'residency' => $residency,
                    'governorate' => $governorate,
                    'marital_status' => $marital_status,
                    'educational_level' => $educational_level,
                    'special_habits_of_the_patient' => $special_habits_of_the_patient,
                    'other_habits_of_the_patient' => $other_habits_of_the_patient,
                    'DM' => $DM,
                    'DM_duration' => $DM_duration,
                    'HTN' => $HTN,
                    'HTN_duration' => $HTN_duration,
                    'other' => $other,
                ]);

                $relatedData = [
                    'doctor_id' => $userId = auth()->user()->id,
                    'patient_id' => $patient->id,
                ];

                $this->section->create(array_merge($relatedData, ['section_1' => true]));
                $this->complaint->create($relatedData);
                $this->cause->create($relatedData);
                $this->risk->create($relatedData);
                $this->assessment->create($relatedData);
                $this->examination->create($relatedData);
                $this->decision->create($relatedData);
                //$this->outcome->create($relatedData);

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

            $submit_status = Section::where('patient_id', $patient->id)->get(['submit_status'])->first();

            $doctorId = auth()->user()->id;
            $doctorIds = User::whereNotIn('id', [$doctorId])->pluck('id');

            foreach ($doctorIds as $doctorId) {
                Notification::create([
                    'content' => 'New Patient was created',
                    'read' => false,
                    'type' => 'New Patient',
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $response = [
                'value' => true,
                'doctor_id' => Auth::id(),
                'id' => $patient->id,
                'name' => $patient->name,
                'submit_status' => $submit_status->submit_status,
                'message' => 'Patient Created Successfully',
            ];

            return response($response, 200);
        } catch (\Exception $e) {
            $response = [
                'value' => false,
                'message' => 'Error: '.$e->getMessage(),
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

        if ($Patient != null) {
            $response = [
                'value' => true,
                'data' => $Patient,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Patient was found',
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

        if ($Patient != null) {
            // $Patient->update($request->all());
            $questionMap = $request->all();
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
                'message' => 'Patient Updated Successfully',
            ];

            return response()->json($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Patient was found',
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

        if ($Patient != null) {
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
                'message' => 'Patient Deleted Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Patient was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Search for product by name
     *
     * @param  string  $name
     * @return \Illuminate\Http\Response
     */
    public function search($name)
    {
        $Patient = PatientHistory::where(function ($query) use ($name) {
            $query->where('name', 'like', '%'.$name.'%')
                ->orWhere('hospital', 'like', '%'.$name.'%')
                ->orWhereHas('doctor', function ($query) use ($name) {
                    $query->where('name', 'like', '%'.$name.'%');
                });
        })
            ->with('doctor:id,name,lname')
            ->with(['sections' => function ($query) {
                $query->select('patient_id', 'submit_status', 'outcome_status');
            }])
            ->latest('updated_at')
            ->get(['id', 'doctor_id', 'name', 'hospital', 'updated_at']);

        if ($Patient != null) {
            $response = [
                'value' => true,
                'data' => $Patient,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Patient was found',
            ];

            return response($response, 404);
        }
    }
}
