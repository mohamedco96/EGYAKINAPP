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
}
