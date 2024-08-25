<?php

namespace App\Models;

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
    protected $casts = ['read' => 'boolean'];

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
}
