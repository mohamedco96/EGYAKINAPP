<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Treatment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'treatment_name',
        'treatment_duration',
        'treatment_dose',
        'treatment_frequency',
        'treatment_notes',
        'treatment_outcome',
        'treatment_side_effects',
        'treatment_compliance',
        'treatment_follow_up',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'treatment_notes',
        'treatment_outcome',
        'treatment_side_effects',
        'treatment_compliance',
        'treatment_follow_up',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'treatment_notes' => 'encrypted',
        'treatment_outcome' => 'encrypted',
        'treatment_side_effects' => 'encrypted',
        'treatment_compliance' => 'encrypted',
        'treatment_follow_up' => 'encrypted',
        'treatment_duration' => 'encrypted',
        'treatment_dose' => 'encrypted',
        'treatment_frequency' => 'encrypted',
    ];

    /**
     * Get the encrypted treatment notes.
     *
     * @param  string|null  $value
     */
    public function getTreatmentNotesAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Set the encrypted treatment notes.
     *
     * @param  string|null  $value
     */
    public function setTreatmentNotesAttribute($value): void
    {
        $this->attributes['treatment_notes'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the encrypted treatment outcome.
     *
     * @param  string|null  $value
     */
    public function getTreatmentOutcomeAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Set the encrypted treatment outcome.
     *
     * @param  string|null  $value
     */
    public function setTreatmentOutcomeAttribute($value): void
    {
        $this->attributes['treatment_outcome'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the encrypted treatment side effects.
     *
     * @param  string|null  $value
     */
    public function getTreatmentSideEffectsAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Set the encrypted treatment side effects.
     *
     * @param  string|null  $value
     */
    public function setTreatmentSideEffectsAttribute($value): void
    {
        $this->attributes['treatment_side_effects'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the encrypted treatment compliance.
     *
     * @param  string|null  $value
     */
    public function getTreatmentComplianceAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Set the encrypted treatment compliance.
     *
     * @param  string|null  $value
     */
    public function setTreatmentComplianceAttribute($value): void
    {
        $this->attributes['treatment_compliance'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the encrypted treatment follow up.
     *
     * @param  string|null  $value
     */
    public function getTreatmentFollowUpAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Set the encrypted treatment follow up.
     *
     * @param  string|null  $value
     */
    public function setTreatmentFollowUpAttribute($value): void
    {
        $this->attributes['treatment_follow_up'] = $value ? Crypt::encryptString($value) : null;
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(PatientHistory::class);
    }
}
