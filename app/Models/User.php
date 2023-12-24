<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'lname',
        'email',
        'password',
        'age',
        'specialty',
        'workingplace',
        'phone',
        'job',
        'highestdegree',
        'blocked',
        'limited'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'blocked' => 'boolean',
        'limited' => 'boolean',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return str_ends_with($this->email, '@egyakin.com');
    }

    public function patients(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PatientHistory::class, 'doctor_id');
    }

    public function sections(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Section::class, 'patient_id');
    }

    public function score(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Score::class, 'doctor_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function outcome(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Outcome::class, 'doctor_id');
    }

    public function likess()
    {
        return $this->hasMany(Likes::class);
    }

    public function posts()
    {
        return $this->hasMany(Posts::class, 'doctor_id');
    }

    public function postcomments()
    {
        return $this->hasMany(PostComments::class, 'doctor_id');
    }

    public function notification()
    {
        return $this->hasMany(Notification::class, 'doctor_id');
    }
}
