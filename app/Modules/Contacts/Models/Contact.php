<?php

namespace App\Modules\Contacts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Database\Factories\Modules\Contacts\ContactFactory;

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
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return ContactFactory::new();
    }

    /**
     * Get the doctor who received the contact message.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
