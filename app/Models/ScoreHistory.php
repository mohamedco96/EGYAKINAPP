<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ScoreHistory extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
