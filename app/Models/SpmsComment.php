<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpmsComment extends Model
{
    use HasFactory;
     
    protected $fillable = [
        'pr_number',
        'comment',
        'status',
        'checkby',
    ];
}
