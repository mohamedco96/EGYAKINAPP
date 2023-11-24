<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Risk extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'doctor_id',
        'patient_id',
        'CKD_history',
        'AK_history',
        'cardiac-failure_history',
        'LCF_history',
        'neurological-impairment_disability_history',
        'sepsis_history',
        'contrast_media',
        'drugs-with-potential-nephrotoxicity',
        'drug_name',
        'hypovolemia_history',
        'malignancy_history',
        'trauma_history',
        'autoimmune-disease_history',
        'other-risk-factors',
        'created_at',
        'updated_at',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(PatientHistory::class);
    }
}
