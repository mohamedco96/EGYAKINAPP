<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedPostComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_post_id', 'doctor_id', 'comment', 'parent_id'
    ];

    public function post()
    {
        return $this->belongsTo(FeedPost::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    // Relationship to get child comments
    public function replies()
    {
        return $this->hasMany(FeedPostComment::class, 'parent_id')->with('replies'); // Recursive relation for nested comments
    }

    // Relationship to get likes for comments
    public function likes()
    {
        return $this->hasMany(FeedPostCommentLike::class, 'post_comment_id');
    }

}
