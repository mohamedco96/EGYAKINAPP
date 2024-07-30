<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationDoctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'consult_doctor_id',
        'reply',
        'status',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function consultDoctor()
    {
        return $this->belongsTo(User::class, 'consult_doctor_id');
    }
}
