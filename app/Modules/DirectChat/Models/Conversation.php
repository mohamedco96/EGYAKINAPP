<?php

namespace App\Modules\DirectChat\Models;

use App\Models\User;
use Database\Factories\DirectChat\ConversationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory;

    protected static function newFactory(): ConversationFactory
    {
        return ConversationFactory::new();
    }

    protected $fillable = [
        'type',
        'name',
        'description',
        'image',
        'created_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['role', 'joined_at', 'last_read_at', 'mute_notifications'])
            ->withTimestamps();
    }

    public function participantRecords(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('participantRecords', fn (Builder $q) => $q->where('user_id', $userId));
    }

    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('type', 'private');
    }

    public function scopeCaseGroup(Builder $query): Builder
    {
        return $query->where('type', 'case_group');
    }

    public function scopeSocialGroup(Builder $query): Builder
    {
        return $query->where('type', 'social_group');
    }
}
