<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Questions extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'section_id',
        'section_name',
        'question',
        'values',
        'type',
        'keyboard_type',
        'mandatory',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'values' => 'array',
        'mandatory' => 'boolean',
    ];

    /**
     * Get the doctor answers for the question.
     */
    public function doctor_answers()
    {
        return $this->hasOne(PatientHistory::class, 'section_id');
    }
}
