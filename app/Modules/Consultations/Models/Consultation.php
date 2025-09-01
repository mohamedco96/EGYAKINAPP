<?php

namespace App\Modules\Consultations\Models;

use App\Models\User;
use App\Modules\Patients\Models\Patients;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'consult_message',
        'status',
        'is_open',
    ];

    protected $casts = [
        'doctor_id' => 'integer',
        'patient_id' => 'integer',
        'is_open' => 'boolean',
    ];

    public function consultationDoctors()
    {
        return $this->hasMany(ConsultationDoctor::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patients::class, 'patient_id');
    }
}
