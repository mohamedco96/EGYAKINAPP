<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Patients extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'doctor_id',
        'hidden',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'hidden' => 'boolean',
    ];

    public function doctor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(Recommendation::class, 'patient_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answers::class, 'patient_id');
    }

    public function status(): HasMany
    {
        return $this->hasMany(PatientStatus::class, 'patient_id');
    }

    // Define the sections relationship
    public function sections(): HasMany
    {
        return $this->hasMany(SectionsInfo::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function notification()
    {
        return $this->hasMany(AppNotification::class, 'doctor_id');
    }

    public function consultations()
    {
        return $this->hasMany(AIConsultation::class);
    }
}
