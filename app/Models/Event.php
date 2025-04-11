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
        'venue',
        'start',
        'end',
        'user_id',
        'campus_id',
        'emp_status',
        'bg_color',
        'remember_token',
        'event_stat',
    ];
}
