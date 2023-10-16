<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Cause extends Model
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
        'cause_of_AKI',
        'pre-renal_causes',
        'pre-renal_others',
        'renal_causes',
        'renal_others',
        'post-renal_causes',
        'post-renal_others',
        'other',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'pre-renal_causes' => 'array',
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
