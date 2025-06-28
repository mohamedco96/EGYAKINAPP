<?php

namespace App\Modules\Patients\Models;

use App\Models\AIConsultation;
use App\Models\Answers;
use App\Models\AppNotification;
use App\Models\Comment;
use App\Models\Recommendation;
use App\Models\SectionsInfo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'doctor_id' => 'integer'
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'doctor_id');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(\App\Modules\Recommendations\Models\Recommendation::class, 'patient_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(\App\Models\Answers::class, 'patient_id');
    }

    public function status(): HasMany
    {
        return $this->hasMany(PatientStatus::class, 'patient_id');
    }

    // Define the sections relationship
    public function sections(): HasMany
    {
        return $this->hasMany(\App\Models\SectionsInfo::class);
    }

    public function comments()
    {
        return $this->hasMany(\App\Models\Comment::class);
    }

    public function notification()
    {
        return $this->hasMany(\App\Modules\Notifications\Models\AppNotification::class, 'doctor_id');
    }

    public function consultations()
    {
        return $this->hasMany(\App\Models\AIConsultation::class);
    }
}
