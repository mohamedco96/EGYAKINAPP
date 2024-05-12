<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Likes extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['doctor_id', 'patient_id', 'liked', 'comment_id'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'liked' => 'boolean',
    ];

    public function doctor()
    {
        return $this->belongsTo(User::class);
    }

    public function patient()
    {
        return $this->belongsTo(PatientHistory::class);
    }

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }
}
