<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'empid',
        'salary',
        'leave_type',
        'leave_purpose',
        'leave_detail',
        'date_range',
        'days',
        'commutation',
        'total_vl',
        'total_sl',
        'recommend',
        'supervisor',
        'sup_sign',
        'sup_sdate',
        'president',
        'pres_sign',
        'pres_sdate',
        'hr',
        'hr_sign',
        'hr_sdate',
        'comment_stat',
        'comment_details',
        'department',
        'date_filing',
        'status',
    ];
    
}
