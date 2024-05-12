<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Examination extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'doctor_id',
        'patient_id',
        'current_creatinine',
        'basal_creatinine',
        'renal_US',
        'specify_renal-US',
        'Other laboratory findings',
        'Other radiology findings',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the doctor who performed the examination.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the patient associated with the examination.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(PatientHistory::class);
    }
}
