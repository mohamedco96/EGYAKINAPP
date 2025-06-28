<?php

namespace App\Modules\Consultations\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Modules\Patients\Models\Patients;

class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'consult_message',
        'status',
    ];

    protected $casts = [
        'doctor_id' => 'integer',
        'patient_id' => 'integer'
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
