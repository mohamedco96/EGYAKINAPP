<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedPost extends Model
{
    use HasFactory;

    protected $fillable = ['doctor_id', 'content', 'media_type', 'media_path', 'visibility'];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }


    public function comments()
    {
        return $this->hasMany(FeedPostComment::class);
    }

    public function likes()
    {
        return $this->hasMany(FeedPostLike::class);
    }

    public function saves()
    {
        return $this->hasMany(FeedSaveLike::class);
    }

    public function hashtags()
    {
        return $this->belongsToMany(Hashtag::class, 'post_hashtags','post_id');
    }
}
