<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class PatientHistory extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'hospital',
        'collected_data_from',
        'NID',
        'phone',
        'email',
        'age',
        'gender',
        'occupation',
        'residency',
        'governorate',
        'marital_status',
        'educational_level',
        'special_habits_of_the_patient',
        'DM',
        'DM_duration',
        'HTN',
        'HTN_duration'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
