<?php

namespace App\Modules\DirectChat\Models;

use App\Models\User;
use Database\Factories\DirectChat\ConversationParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    use HasFactory;

    protected static function newFactory(): ConversationParticipantFactory
    {
        return ConversationParticipantFactory::new();
    }

    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'joined_at',
        'last_read_at',
        'mute_notifications',
    ];

    protected $casts = [
        'conversation_id' => 'integer',
        'user_id' => 'integer',
        'joined_at' => 'datetime',
        'last_read_at' => 'datetime',
        'mute_notifications' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
