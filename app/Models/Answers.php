<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answers extends Model
{
    use HasFactory;

    protected $fillable = ['patient_id', 'section_id', 'question_id', 'doctor_id', 'answer','answer_type'];

    protected $casts = [
        'answer' => 'array',
        'patient_id' => 'integer',
        'section_id' => 'integer',
        'question_id' => 'integer',
        'doctor_id' => 'integer'
    ];

    public function patient()
    {
        return $this->belongsTo(Patients::class, 'patient_id');
    }

    public function section()
    {
        return $this->belongsTo(SectionsInfo::class, 'section_id');
    }

    public function question()
    {
        return $this->belongsTo(Questions::class, 'question_id');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function notification()
    {
        return $this->belongsTo(AppNotification::class, 'patient_id');
    }
}
