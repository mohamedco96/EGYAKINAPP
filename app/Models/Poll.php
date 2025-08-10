<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
    protected $fillable = ['feed_post_id', 'question', 'allow_add_options', 'allow_multiple_choice'];

    public function post()
    {
        return $this->belongsTo(FeedPost::class, 'feed_post_id');
    }

    public function options()
    {
        return $this->hasMany(PollOption::class, 'poll_id');
    }

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'feed_post_id' => 'integer',
        'allow_add_options' => 'boolean',
        'allow_multiple_choice' => 'boolean',
    ];
}
