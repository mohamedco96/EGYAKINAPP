<?php

namespace App\Modules\Questions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PatientHistory;
use App\Models\SectionsInfo;
use App\Models\Answers;

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
        'hidden',
        'skip',
        'sort',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'values' => 'array',
        'mandatory' => 'boolean',
        'hidden' => 'boolean',
        'skip' => 'boolean',
        'section_id' => 'integer',
        'sort' => 'integer',
    ];

    /**
     * The attributes with their default values.
     *
     * @var array
     */
    protected $attributes = [
        'mandatory' => false,
        'hidden' => false,
        'skip' => false,
        'sort' => 0,
    ];

    /**
     * Get the doctor answers for the question.
     */
    public function doctor_answers()
    {
        return $this->hasOne(PatientHistory::class, 'section_id');
    }

    public function section()
    {
        return $this->belongsTo(SectionsInfo::class);
    }

    public function answers()
    {
        return $this->hasMany(Answers::class,'question_id');
    }
}
