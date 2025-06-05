<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpmsPersonnel extends Model
{
    use HasFactory;
    protected $fillable = [
        'empid', 'category', 'off_coll_id', 'position', 'emp_position', 'designation'
    ];
}
