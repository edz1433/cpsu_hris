<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Eligibility extends Model
{
    use HasFactory;

    protected $fillable = [
        'empid',
        'career_eligible',
        'rating',
        'date_exam',
        'place_exam',
        'number',
        'date_valid',
        'attachment',
        'status',
    ];

    public $timestamps = false;
}
