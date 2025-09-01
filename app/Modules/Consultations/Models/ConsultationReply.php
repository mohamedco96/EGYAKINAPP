<?php

namespace App\Modules\Consultations\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_doctor_id',
        'reply',
    ];

    protected $casts = [
        'consultation_doctor_id' => 'integer',
    ];

    public function consultationDoctor()
    {
        return $this->belongsTo(ConsultationDoctor::class);
    }
}
