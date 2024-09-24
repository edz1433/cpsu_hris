<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    protected $fillable = [
        'id',
        'empid',
        'position',
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
        'sup_prefix',
        'sup_sign',
        'sup_sdate',
        'president',
        'pres_prefix',
        'pres_sign',
        'pres_sdate',
        'hr',
        'hr_prefix',
        'hr_sign',
        'hr_sdate',
        'remarks_stat',
        'remarks_details',
        'department',
        'date_filing',
        'day_wpay',
        'earn',
        'less',
        'balance',
        'status',
        'gen_app',
    ];

    public function office()
    {
        return $this->belongsTo(Office::class, 'department', 'id');
    }
    
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'empid', 'emp_ID');
    }

    // public function leaveApplications()
    // {
    //     return $this->hasMany(LeaveApplication::class, 'empid', 'emp_ID');
    // }

}
