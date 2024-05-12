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
        'doctor_id',
        'section_id',
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
        'other',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        //'id' => 'string', // Cast primary key to string
        //'foreign_key' => 'string', // Cast foreign key to string
        //'doctor_id' => 'string',
       //'patient_id' => 'string',
        'special_habits_of_the_patient' => 'array',
        'hidden' => 'boolean'
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function sections(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Section::class, 'patient_id');
    }

    public function complaint(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Complaint::class, 'patient_id');
    }

    public function cause(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Cause::class, 'patient_id');
    }

    public function risk(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Risk::class, 'patient_id');
    }

    public function assessment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Assessment::class, 'patient_id');
    }

    public function examination(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Examination::class, 'patient_id');
    }

    public function decision(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Decision::class, 'patient_id');
    }

    public function outcome(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Outcome::class, 'patient_id');
    }

    public function questions(): BelongsTo
    {
        return $this->belongsTo(Questions::class, 'section_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function notification()
    {
        return $this->hasMany(Notification::class, 'doctor_id');
    }
}
