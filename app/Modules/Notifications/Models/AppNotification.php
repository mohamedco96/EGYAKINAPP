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
    protected $fillable = ['content', 'read', 'patient_id', 'type', 'type_id', 'doctor_id'];

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
}
