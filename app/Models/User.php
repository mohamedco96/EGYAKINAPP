<?php

namespace App\Models;

use App\Modules\Achievements\Models\Achievement;
use App\Modules\Notifications\Models\FcmToken;
use App\Modules\Patients\Models\Patients;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, HasPermissions, HasRoles, Notifiable;

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
        'email_verified_at',
        'locale',
        'google_id',
        'apple_id',
        'avatar',
        'social_verified_at',
        'profile_completed',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'passwordValue',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'social_verified_at' => 'datetime',
        'blocked' => 'boolean',
        'limited' => 'boolean',
        'profile_completed' => 'boolean',
        'locale' => 'string',
    ];

    /**
     * Get the user's image URL with prefix.
     *
     * @param  string|null  $value
     */
    public function getImageAttribute($value): ?string
    {
        return $this->getPrefixedUrl($value);
    }

    /**
     * Get the user's syndicate card URL with prefix.
     *
     * @param  string|null  $value
     */
    public function getSyndicateCardAttribute($value): ?string
    {
        return $this->getPrefixedUrl($value);
    }

    /**
     * Get the URL with prefix.
     *
     * @param  string|null  $value
     */
    private function getPrefixedUrl($value): ?string
    {
        if (! $value) {
            return null;
        }

        // Add your prefix here
        $prefix = config('app.url').'/'.'storage/';

        return $prefix.$value;
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
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // TEMPORARY: Allow all users to access Filament panel
        // TODO: Restore proper access control after fixing permission issues
        return true;

        // Original access control (commented out temporarily):
        // return $this->hasRole(['Admin', 'Tester']) ||
        //        str_ends_with($this->email, '@egyakin.com') ||
        //        in_array($this->email, [
        //            'mohamedco215@gmail.com',
        //            'Darsh1980@mans.edu.eg',
        //            'aboelkhaer@yandex.com',
        //        ]);
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

    //    public function likess()
    //    {
    //        return $this->hasMany(Likes::class);
    //    }

    public function posts()
    {
        return $this->hasMany(\App\Modules\Posts\Models\Posts::class, 'doctor_id');
    }

    public function postcomments()
    {
        return $this->hasMany(\App\Modules\Posts\Models\PostComments::class, 'doctor_id');
    }

    public function notification()
    {
        return $this->hasMany(AppNotification::class, 'doctor_id');
    }

    public function PatientStatus(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PatientStatus::class);
    }

    //    New
    public function feedPosts()
    {
        return $this->hasMany(FeedPost::class, 'doctor_id');
    }

    public function likes()
    {
        return $this->hasMany(FeedPostLike::class);
    }

    public function saves()
    {
        return $this->hasMany(FeedSaveLike::class, 'doctor_id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_user', 'doctor_id', 'group_id')
            ->withTimestamps();
    }

    public function trials()
    {
        return $this->hasOne(DoctorMonthlyTrial::class);
    }

    public function consultations()
    {
        return $this->hasMany(AIConsultation::class);
    }

    public function fcmTokens()
    {
        return $this->hasMany(FcmToken::class, 'doctor_id');
    }

    /**
     * Find user by social provider ID
     */
    public static function findBySocialId($provider, $id)
    {
        return static::where($provider.'_id', $id)->first();
    }

    /**
     * Create user from social provider data
     */
    public static function createFromSocial($provider, $socialUser)
    {
        // Extract name with fallbacks
        $name = $socialUser->getName() ?: $socialUser->getNickname();

        // If still no name, use email username or generate a placeholder
        if (! $name && $socialUser->getEmail()) {
            $name = explode('@', $socialUser->getEmail())[0];
        } elseif (! $name) {
            $name = ucfirst($provider).' User'; // Fallback: "Apple User" or "Google User"
        }

        // Handle email - Apple sometimes doesn't provide it
        $email = $socialUser->getEmail();
        if (! $email) {
            // Generate a placeholder email for Apple users without email
            $email = $socialUser->getId().'@'.$provider.'-user.egyakin.com';
        }

        // Check if we have real email and name (not placeholders)
        $hasRealEmail = $socialUser->getEmail() && ! str_contains($email, '@apple-user.egyakin.com') && ! str_contains($email, '@google-user.egyakin.com');
        $hasRealName = $socialUser->getName() || $socialUser->getNickname();

        // Profile is complete only if both name and email are provided
        $profileCompleted = $hasRealEmail && $hasRealName;

        $userData = [
            'name' => $name,
            'email' => $email,
            'avatar' => $socialUser->getAvatar(),
            'social_verified_at' => now(),
            'password' => bcrypt(\Illuminate\Support\Str::random(32)), // Random password for social users
            'profile_completed' => $profileCompleted,
        ];

        $userData[$provider.'_id'] = $socialUser->getId();

        return static::create($userData);
    }

    /**
     * Get the user's marked patients
     */
    public function markedPatients()
    {
        return $this->belongsToMany(Patients::class, 'marked_patients', 'user_id', 'patient_id')
            ->withTimestamps();
    }
}
