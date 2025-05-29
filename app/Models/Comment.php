<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'doctor_id',
        'patient_id',
        'content',
    ];

    protected $casts = [
        'doctor_id' => 'integer',
        'patient_id' => 'integer'
    ];

    /**
     * Get the doctor that owns the comment.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the patient history associated with the comment.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patients::class, 'patient_id');
    }

    /**
     * Get the likes for the comment.
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Likes::class);
    }
}
