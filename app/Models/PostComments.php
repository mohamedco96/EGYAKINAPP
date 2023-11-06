<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class PostComments extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['content','doctor_id','post_id'];

    // Define the relationships
    public function doctor()
    {
        return $this->belongsTo(User::class);
    }

    public function Posts()
    {
        return $this->belongsTo(Posts::class);
    }
}
