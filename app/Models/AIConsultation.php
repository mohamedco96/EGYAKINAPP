<?php

namespace App\Models;

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
}
