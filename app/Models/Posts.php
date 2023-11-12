<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Posts extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['title', 'image', 'content', 'hidden', 'doctor_id'];

    protected $casts = ['hidden' => 'boolean'];

    // Define the relationships
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function postcomments()
    {
        return $this->hasMany(PostComments::class, 'post_id');
    }
}
