<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Camp extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus_name',
        'campus_abbr',
        'short',
    ];

    protected $connection = 'dbcpsuhris';
    protected $table = 'campuses';
}