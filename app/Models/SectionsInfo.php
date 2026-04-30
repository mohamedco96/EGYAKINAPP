<?php

namespace App\Models;

use App\Modules\Questions\Models\Questions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectionsInfo extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'section_name',
        'section_description',
        'ai_mode',
        'ai_hint',
        'ai_voice_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'ai_voice_time' => 'integer',
    ];

    public function questions()
    {
        return $this->hasMany(Questions::class, 'section_id');
    }
}
