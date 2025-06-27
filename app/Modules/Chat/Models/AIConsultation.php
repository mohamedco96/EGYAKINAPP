<?php

namespace App\Modules\Chat\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class AIConsultation extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    
    protected $table = 'ai_consultations'; // Explicitly set the table name
    protected $fillable = ['doctor_id', 'patient_id', 'question', 'response'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'doctor_id' => 'integer',
        'patient_id' => 'integer',
    ];

    /**
     * Get the doctor that owns the consultation.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'doctor_id');
    }

    /**
     * Get the patient that the consultation is for.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Patients\Models\Patients::class, 'patient_id');
    }
}
