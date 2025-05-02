<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dpcr extends Model
{
    use HasFactory;
    protected $fillable = [
        'opcr_id', 'mfo', 'percent', 'count'
    ];
}
