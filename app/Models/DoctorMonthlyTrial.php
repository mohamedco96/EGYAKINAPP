<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class DoctorMonthlyTrial extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = ['doctor_id', 'trial_count', 'reset_date'];

    protected $casts = [
        'doctor_id' => 'integer',
        'trial_count' => 'integer'
    ];
}
