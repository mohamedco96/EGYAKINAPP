<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedPostLike extends Model
{
    use HasFactory;

    protected $fillable = ['feed_post_id', 'doctor_id'];

    protected $casts = [
        'feed_post_id' => 'integer',
        'doctor_id' => 'integer'
    ];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function post()
    {
        return $this->belongsTo(FeedPost::class);
    }
}
