<?php

namespace App\Modules\Posts\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostComments extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'content',
        'doctor_id',
        'post_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'doctor_id' => 'integer',
        'post_id' => 'integer'
    ];

    /**
     * Get the doctor that owns the comment.
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the post that the comment belongs to.
     */
    public function Posts()
    {
        return $this->belongsTo(Posts::class, 'post_id');
    }
}
