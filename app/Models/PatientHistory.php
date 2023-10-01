<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'owner_id',
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
        'HTN_duration',
        'created_at',
        'updated_at'
    ];
    protected $casts = [
        'special_habits_of_the_patient' => 'array',
    ];
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }
    public function sections(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Section::class,'patient_id');
    }
    public function complaint(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Complaint::class);
    }
    public function Cause(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Complaint::class);
    }
}
