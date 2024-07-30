<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;
 
    protected $primaryKey = 'id';
 
    protected $fillable = [
        'title',
        'start',
        'end',
        'user_id',
        'remember_token',
    ];
}
