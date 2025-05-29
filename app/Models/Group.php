<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'header_picture', 'group_image', 'privacy', 'owner_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'owner_id' => 'integer'
    ];

    // Define the relationship to the owner (User)
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // Define many-to-many relationship with users (members)
    public function doctors()
    {
        return $this->belongsToMany(User::class, 'group_user', 'group_id', 'doctor_id')
                    ->withTimestamps()
                    ->withCasts(['id' => 'integer']); // Cast the pivot table ID (invitation_id) to integer
    }

    // Define relation with Post
    public function posts()
    {
        return $this->hasMany(FeedPost::class);
    }
}
