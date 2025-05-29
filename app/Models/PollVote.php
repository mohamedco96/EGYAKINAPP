<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollVote extends Model
{
    use HasFactory;

    protected $fillable = ['poll_option_id', 'doctor_id'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'poll_option_id' => 'integer',
        'doctor_id' => 'integer'
    ];

    public function option()
    {
        return $this->belongsTo(PollOption::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id'); 
    }
}