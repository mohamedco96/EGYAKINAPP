<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Section extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'patient_id',
        'section_1',
        'section_2',
        'section_3',
        'section_4',
        'section_5',
        'section_6',
        'section_7',
        'submit_status',
        'outcome_status',
        'doc_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'section_1' => 'boolean',
        'section_2' => 'boolean',
        'section_3' => 'boolean',
        'section_4' => 'boolean',
        'section_5' => 'boolean',
        'section_6' => 'boolean',
        'section_7' => 'boolean',
        'submit_status' => 'boolean',
        'outcome_status' => 'boolean'
    ];
}
