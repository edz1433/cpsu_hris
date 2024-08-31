<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollEmployee extends Model
{
    protected $connection = 'payroll';
    protected $table = 'employees';
    protected $fillable = [
        'emp_ID', 'lname', 'fname', 'mname', 'position', 'emp_dept', 'camp_id', 'emp_status'
    ];
}
