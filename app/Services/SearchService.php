<?php

namespace App\Services;

use App\Models\User;
use App\Modules\Doses\Models\Dose;
use App\Modules\Patients\Models\Patients;

class SearchService
{
    /**
     * Search for patients and doses based on query parameters
     */
    public function search(?string $patientQuery = '', ?string $doseQuery = ''): array
    {
        $doses = $this->searchDoses($doseQuery ?? '');
        $patients = $this->searchPatients($patientQuery ?? '');

        return [
            'patients' => $patients,
            'doses' => $doses,
        ];
    }

    /**
     * Search for doses by title
     */
    private function searchDoses(string $query): \Illuminate\Support\Collection
    {
        if (empty($query)) {
            return collect();
        }

        return Dose::select('id', 'title', 'description', 'dose', 'created_at')
            ->where('title', 'like', '%'.$query.'%')
            ->latest('updated_at')
            ->get();
    }

    /**
     * Search for patients by doctor name or answer content
     */
    private function searchPatients(string $query): \Illuminate\Support\Collection
    {
        if (empty($query)) {
            return collect();
        }

        $patients = Patients::select('id', 'doctor_id', 'updated_at')
            ->where('hidden', false)
            ->where(function ($queryBuilder) use ($query) {
                $queryBuilder->whereHas('doctor', function ($doctorQuery) use ($query) {
                    $doctorQuery->where('name', 'like', '%'.$query.'%');
                })
                    ->orWhereHas('answers', function ($answerQuery) use ($query) {
                        $answerQuery->where('answer', 'like', '%'.$query.'%');
                    });
            })
            ->with([
                'doctor:id,name,lname,image,syndicate_card,isSyndicateCardRequired',
                'status:id,patient_id,key,status,doctor_id',
                'answers:id,patient_id,answer,question_id',
            ])
            ->latest('updated_at')
            ->get();

        // Collect all unique submitter IDs to fetch in a single query
        $submitterIds = $patients->flatMap(function ($patient) {
            return $patient->status->where('key', 'outcome_status')
                ->pluck('doctor_id')
                ->filter();
        })->unique()->values();

        // Fetch all submitters in a single query
        $submitters = collect();
        if ($submitterIds->isNotEmpty()) {
            $submitters = User::select('id', 'name', 'lname', 'isSyndicateCardRequired')
                ->whereIn('id', $submitterIds)
                ->get()
                ->keyBy('id');
        }

        return $patients->map(function ($patient) use ($submitters) {
            return $this->transformPatientForSearch($patient, $submitters);
        });
    }

    /**
     * Transform patient data for search results
     */
    private function transformPatientForSearch($patient, $submitters = null): array
    {
        $submitStatus = optional($patient->status->where('key', 'submit_status')->first())->status;
        $outcomeStatus = optional($patient->status->where('key', 'outcome_status')->first())->status;
        $outcomeSubmitterDoctorId = optional($patient->status->where('key', 'outcome_status')->first())->doctor_id;

        $submitter = null;
        if ($outcomeSubmitterDoctorId && $submitters) {
            $submitter = $submitters->get($outcomeSubmitterDoctorId);
        }

        $nameAnswer = optional($patient->answers->where('question_id', 1)->first())->answer;
        $hospitalAnswer = optional($patient->answers->where('question_id', 2)->first())->answer;

        return [
            'id' => $patient->id,
            'doctor_id' => (int) $patient->doctor_id,
            'name' => $nameAnswer,
            'hospital' => $hospitalAnswer,
            'updated_at' => $patient->updated_at,
            'doctor' => $patient->doctor,
            'sections' => [
                'patient_id' => $patient->id,
                'submit_status' => $submitStatus ?? false,
                'outcome_status' => $outcomeStatus ?? false,
                'submitter_id' => optional($submitter)->id,
                'submitter_name' => ($submitter && $submitter->name && $submitter->lname)
                    ? $submitter->name.' '.$submitter->lname
                    : null,
                'submitter_SyndicateCard' => optional($submitter)->isSyndicateCardRequired,
            ],
        ];
    }
}
