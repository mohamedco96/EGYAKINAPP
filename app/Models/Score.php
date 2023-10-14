<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Score extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
