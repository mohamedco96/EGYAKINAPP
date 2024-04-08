<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
     * @var array<int, string>
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
        'registration_number'
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
     * Get the user's image URL with prefix.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getImageAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        // Add your prefix here
        $prefix = config('app.url') . '/' . 'storage/app/public/';
        return $prefix . $value;
    }



    /**
     * Get the user's syndicate card URL with prefix.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getSyndicateCardAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        // Add your prefix here
        $prefix = config('app.url') . '/' . 'storage/app/public/';
        return $prefix . $value;
    }



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

    /**
     * Route notifications for the mail channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    public function routeNotificationForMail($notification)
    {
        // Check if the notification is ContactRequestNotification
        if ($notification instanceof \App\Notifications\ContactRequestNotification) {
            // Return the specific email address for this notification
            return ['mostafa_abdelsalam@egyakin.com', 'Darsh1980@mans.edu.eg'];
        }

        // For other notifications, use the default behavior
        return $this->email;
    }

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
