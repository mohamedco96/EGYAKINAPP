<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedPostCommentLike extends Model
{
    protected $fillable = [
        'post_comment_id', 'doctor_id'
    ];

    public function comment()
    {
        return $this->belongsTo(FeedPostComment::class, 'post_comment_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
}

