<?php

namespace App\Models;

use App\Modules\Patients\Models\Patients;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientSectionAiLog extends Model
{
    protected $fillable = [
        'patient_id',
        'section_id',
        'doctor_id',
        'input_type',
        'extracted_text',
        'prompt',
        'response',
    ];

    protected function casts(): array
    {
        return [
            'patient_id' => 'integer',
            'section_id' => 'integer',
            'doctor_id' => 'integer',
            'response' => 'array',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patients::class, 'patient_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(SectionsInfo::class, 'section_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
