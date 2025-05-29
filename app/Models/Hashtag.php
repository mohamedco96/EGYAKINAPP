<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hashtag extends Model
{
    use HasFactory;

    protected $fillable = ['tag', 'usage_count'];

    public function posts()
    {
        return $this->belongsToMany(FeedPost::class, 'post_hashtags', 'hashtag_id', 'post_id');
    }

    protected $casts = [
    'usage_count' => 'integer'
];
}
