<?php

namespace App\Modules\Posts\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Posts extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'image',
        'content',
        'hidden',
        'doctor_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'hidden' => 'boolean',
        'doctor_id' => 'integer',
    ];

    /**
     * Get the doctor that owns the post.
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the comments for the post.
     */
    public function postcomments()
    {
        return $this->hasMany(PostComments::class, 'feed_post_id');
    }
}
