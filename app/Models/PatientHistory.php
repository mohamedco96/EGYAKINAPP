<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
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
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'NID',
        'phone',
        'email',
        'special_habits_of_the_patient',
        'DM',
        'DM_duration',
        'HTN',
        'HTN_duration',
        'other',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'special_habits_of_the_patient' => 'encrypted:array',
        'DM' => 'encrypted',
        'DM_duration' => 'encrypted',
        'HTN' => 'encrypted',
        'HTN_duration' => 'encrypted',
        'other' => 'encrypted',
        'NID' => 'encrypted',
        'phone' => 'encrypted',
        'email' => 'encrypted',
        'hidden' => 'boolean',
    ];

    /**
     * Get the encrypted NID.
     *
     * @param  string|null  $value
     */
    public function getNIDAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Set the encrypted NID.
     *
     * @param  string|null  $value
     */
    public function setNIDAttribute($value): void
    {
        $this->attributes['NID'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the encrypted phone number.
     *
     * @param  string|null  $value
     */
    public function getPhoneAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Set the encrypted phone number.
     *
     * @param  string|null  $value
     */
    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the encrypted email.
     *
     * @param  string|null  $value
     */
    public function getEmailAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Set the encrypted email.
     *
     * @param  string|null  $value
     */
    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the encrypted special habits.
     *
     * @param  array|null  $value
     */
    public function getSpecialHabitsOfThePatientAttribute($value): ?array
    {
        return $value ? json_decode(Crypt::decryptString($value), true) : null;
    }

    /**
     * Set the encrypted special habits.
     *
     * @param  array|null  $value
     */
    public function setSpecialHabitsOfThePatientAttribute($value): void
    {
        $this->attributes['special_habits_of_the_patient'] = $value ? Crypt::encryptString(json_encode($value)) : null;
    }

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
}
