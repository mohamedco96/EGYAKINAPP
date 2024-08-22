<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'score', 'image'];

    /**
     * Get the Achievement's image URL with prefix.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getImageAttribute($value): ?string
    {
        return $this->getPrefixedUrl($value);
    }

    /**
     * Get the URL with prefix.
     *
     * @param  string|null  $value
     * @return string|null
     */
    private function getPrefixedUrl($value): ?string
    {
        if (!$value) {
            return null;
        }

        // Add your prefix here
        $prefix = config('app.url') . '/' . 'storage/';
        return $prefix . $value;
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_achievements')->withPivot('achieved')->withTimestamps();
    }
}
