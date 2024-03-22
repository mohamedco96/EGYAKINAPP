<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectionFieldMapping extends Model
{
    use HasFactory;
    protected $fillable = ['section_id', 'field_name', 'column_name'];
}
