<?php

namespace App\Modules\Notifications\Models;

use App\Models\User;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Patients\Models\Patients;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

class AppNotification extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notifications';

    use HasApiTokens, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'content',
        'read',
        'patient_id',
        'type',
        'type_id',
        'doctor_id',
        'type_doctor_id',
        'localization_key',
        'localization_params',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'read' => 'boolean',
        'patient_id' => 'integer',
        'doctor_id' => 'integer',
        'type_id' => 'integer',
        'localization_params' => 'array',
    ];

    /**
     * Get the doctor associated with the notification.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the patient associated with the notification.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patients::class, 'patient_id');
    }

    public function typeDoctor()
    {
        return $this->belongsTo(User::class, 'type_doctor_id', 'id');
    }

    /**
     * Get the consultation associated with the notification (when type is 'Consultation')
     */
    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class, 'type_id', 'id');
    }

    /**
     * Get the localized content for the notification
     */
    public function getLocalizedContent(?string $locale = null): string
    {
        // If we have a localization key, use it
        if ($this->localization_key) {
            // Set the locale temporarily if provided
            if ($locale) {
                $originalLocale = app()->getLocale();
                app()->setLocale($locale);
            }

            // Get localized content
            $localizedContent = __($this->localization_key, $this->localization_params ?? []);

            // Restore original locale if it was changed
            if ($locale && isset($originalLocale)) {
                app()->setLocale($originalLocale);
            }

            return $localizedContent;
        }

        // Fallback to original content if no localization key
        return $this->content ?? '';
    }

    /**
     * Create a localized notification
     *
     * @return static
     */
    public static function createLocalized(array $data): self
    {
        // If localization_key is provided, generate fallback content in English
        if (isset($data['localization_key'])) {
            $originalLocale = app()->getLocale();
            app()->setLocale('en'); // Set to English for fallback content

            $data['content'] = __($data['localization_key'], $data['localization_params'] ?? []);

            app()->setLocale($originalLocale);
        }

        return static::create($data);
    }

    /**
     * Accessor for localized content (automatically uses current locale)
     */
    public function getLocalizedContentAttribute(): string
    {
        return $this->getLocalizedContent();
    }
}
