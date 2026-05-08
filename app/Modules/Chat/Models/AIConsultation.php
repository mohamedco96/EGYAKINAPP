<?php

namespace App\Modules\Chat\Models;

use App\Models\User;
use App\Modules\Patients\Models\Patients;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the patient that the consultation is for.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patients::class, 'patient_id');
    }
}
