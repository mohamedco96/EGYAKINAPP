<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Consultation;
use App\Models\ConsultationDoctor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsultationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'consult_message' => 'required|string',
            'consult_doctor_ids' => 'required|array',
            'consult_doctor_ids.*' => 'exists:users,id',
        ]);

        $consultation = Consultation::create([
            'doctor_id' => Auth::id(),
            'patient_id' => $request->patient_id,
            'consult_message' => $request->consult_message,
            'status' => 'pending',
        ]);

        foreach ($request->consult_doctor_ids as $consult_doctor_id) {
            ConsultationDoctor::create([
                'consultation_id' => $consultation->id,
                'consult_doctor_id' => $consult_doctor_id,
                'status' => 'pending',
            ]);
        }

        $response = [
            'value' => true,
            'data' => $consultation,
            'message' => 'Consultation Created Successfully',
        ];

        return response($response, 201);
    }

    public function sentRequests()
    {
        $consultations = Consultation::where('doctor_id', Auth::id())->with('consultationDoctors')->get();
        return response()->json($consultations);
    }

    public function receivedRequests()
    {
        $consultations = ConsultationDoctor::where('consult_doctor_id', Auth::id())->with('consultation')->get();
        return response()->json($consultations);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'reply' => 'required|string',
            'status' => 'required|string|in:accepted,rejected,replied',
        ]);

        $consultationDoctor = ConsultationDoctor::where('consultation_id', $id)
            ->where('consult_doctor_id', Auth::id())
            ->firstOrFail();

        $consultationDoctor->reply = $request->reply;
        $consultationDoctor->status = $request->status;
        $consultationDoctor->save();

        // Check if all consultation doctors have replied
        $allReplied = ConsultationDoctor::where('consultation_id', $id)
                ->where('status', '!=', 'replied')
                ->count() === 0;

        if ($allReplied) {
            $consultationDoctor->consultation->status = 'complete';
            $consultationDoctor->consultation->save();
        }

        return response()->json(['message' => 'Consultation request updated successfully']);
    }

    public function consultationSearch($data)
    {
        try {
            // Retrieve Users
            $users = User::select('id', 'name', 'email', 'phone', 'specialty', 'workingplace', 'image', 'syndicate_card', 'isSyndicateCardRequired')
                ->where('name', 'like', '%' . $data . '%')
                ->orwhere('email', 'like', '%' . $data . '%')
                ->orwhere('phone', 'like', '%' . $data . '%')
                ->withCount('patients')
                ->selectSub(function ($query) {
                    $query->selectRaw('COALESCE(score, 0)')
                        ->from('scores')
                        ->whereColumn('users.id', 'scores.doctor_id')
                        ->limit(1);
                }, 'score')
                ->orderByRaw('COALESCE(score, 0) DESC, patients_count DESC')
                ->limit(5)
                ->get()
                ->map(function ($user) {
                    $user->patients_count = strval($user->patients_count);
                    return $user;
                });

            return response()->json([
                'value' => true,
                'data' => $users ], 200);

        } catch (\Exception $e) {
            // Log error
            Log::error('Error searching for data.', ['exception' => $e]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to search for data.',
            ], 500);
        }
    }

}
