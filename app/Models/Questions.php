<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Questions extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'section_id',
        'section_name',
        'question',
        'values',
        'type',
        'mandatory'
    ];

    protected $casts = [
        'values' => 'array',
        'mandatory' => 'boolean',
    ];

    public function doctor_answers(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PatientHistory::class,'section_id');
    }
}
