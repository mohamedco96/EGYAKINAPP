<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Traits\HasPermissions;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'lname',
        'image',
        'syndicate_card',
        'email',
        'password',
        'age',
        'specialty',
        'workingplace',
        'phone',
        'job',
        'highestdegree',
        'blocked',
        'limited',
        'gender',
        'image',
        'birth_date',
        'role',
        'registration_number',
        'version',
        'isSyndicateCardRequired',
        'blocked',
        'email_verified_at'

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'passwordValue'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'blocked' => 'boolean',
        'limited' => 'boolean',
    ];

    /**
     * Get the user's image URL with prefix.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getImageAttribute($value): ?string
    {
        return $this->getPrefixedUrl($value);
    }

    /**
     * Get the user's syndicate card URL with prefix.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getSyndicateCardAttribute($value): ?string
    {
        return $this->getPrefixedUrl($value);
    }

    /**
     * Get the URL with prefix.
     *
     * @param  string|null  $value
     * @return string|null
     */
    private function getPrefixedUrl($value): ?string
    {
        if (!$value) {
            return null;
        }

        // Add your prefix here
        $prefix = config('app.url') . '/' . 'storage/';
        return $prefix . $value;
    }

    /**
     * Route notifications for the mail channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string|array
     */
    public function routeNotificationForMail($notification)
    {
        if ($notification instanceof \App\Notifications\ContactRequestNotification) {
            // Return the specific email addresses for this notification
            return ['mostafa_abdelsalam@egyakin.com', 'Darsh1980@mans.edu.eg'];
        }

        return $this->email;
    }

    /**
     * Check if user can access the panel.
     *
     * @param  Panel  $panel
     * @return bool
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return str_ends_with($this->email, '@egyakin.com');
    }

    public function patients()
    {
        return $this->hasMany(Patients::class, 'doctor_id');
    }

    /**
     * Define relationships.
     */
    /*public function patients()
    {
        return $this->hasOne(PatientHistory::class, 'doctor_id');
    }*/

    public function sections()
    {
        return $this->hasOne(Section::class, 'patient_id');
    }

    public function score()
    {
        return $this->hasOne(Score::class, 'doctor_id');
    }

    public function scoreHistory()
    {
        return $this->hasOne(ScoreHistory::class, 'doctor_id');
    }

    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')->withPivot('achieved')->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function outcome()
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
        return $this->hasMany(AppNotification::class, 'doctor_id');
    }

    public function PatientStatus(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PatientStatus::class);
    }
}
