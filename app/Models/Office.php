<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    use HasFactory;

    protected $connection = 'payroll';
    protected $table = 'offices';
    
    protected $fillable = ['office_name', 'office_abbr', 'office_head_id', 'group_by'];

}
