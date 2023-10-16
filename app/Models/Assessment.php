<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Assessment extends Model
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
        'heart-rate/minute',
        'respiratory-rate/minute',
        'SBP',
        'DBP',
        'GCS',
        'oxygen_saturation',
        'temperature',
        'UOP',
        'AVPU',
        'skin_examination',
        'skin_examination_clarify',
        'eye_examination',
        'eye_examination_clarify',
        'ear_examination',
        'ear_examination_clarify',
        'cardiac_examination',
        'cardiac_examination_clarify',
        'internal_jugular_vein',
        'chest_examination',
        'chest_examination_clarify',
        'abdominal_examination',
        'abdominal_examination_clarify',
        'other',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'skin_examination' => 'array',
        'eye_examination' => 'array',
        'cardiac_examination' => 'array',
        'chest_examination' => 'array',
        'abdominal_examination' => 'array',
    ];
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(PatientHistory::class);
    }
}
