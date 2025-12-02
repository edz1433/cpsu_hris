<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DpcrMfo extends Model
{
    use HasFactory;
    protected $fillable = [
        'dpcr_id',
        'opcr_id',
        'mfo',
        'functions',
        'percent',
        'count'
    ];
}
