<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Section extends Model
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
        'section_1',
        'section_2',
        'section_3',
        'section_4',
        'section_5',
        'section_6',
        'section_7',
        'submit_status',
        'outcome_status',
        'doc_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'section_1' => 'boolean',
        'section_2' => 'boolean',
        'section_3' => 'boolean',
        'section_4' => 'boolean',
        'section_5' => 'boolean',
        'section_6' => 'boolean',
        'section_7' => 'boolean',
        'submit_status' => 'boolean',
        'outcome_status' => 'boolean',
    ];

    /**
     * Define the relationship with the doctor.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Define the relationship with the patient.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(PatientHistory::class);
    }

    /**
     * Define the relationship with the complaint.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function complaint(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Complaint::class, 'patient_id');
    }

    /**
     * Define the relationship with the cause.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function cause(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Cause::class, 'patient_id');
    }

    /**
     * Define the relationship with the risk.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function risk(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Risk::class, 'patient_id');
    }

    /**
     * Define the relationship with the assessment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function assessment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Assessment::class, 'patient_id');
    }

    /**
     * Define the relationship with the examination.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function examination(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Examination::class, 'patient_id');
    }
}
