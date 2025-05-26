<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class DoctorMonthlyTrial extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['doctor_id', 'trial_count', 'reset_date'];
}
