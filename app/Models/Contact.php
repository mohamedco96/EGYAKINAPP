<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'doctor_id',
        'message',
    ];

    protected $casts = [
        'doctor_id' => 'integer'
    ];

    /**
     * Get the doctor who received the contact message.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
