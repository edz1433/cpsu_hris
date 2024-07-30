<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $connection = 'mysql';
    protected $table = 'employees';
    
    protected $fillable = [
        'fname', 'mname', 'lname', 'position', 'profile', 'sg_step', 'qualification',
        'camp_id', 'emp_ID', 'emp_status', 'emp_dept', 'item_no', 'partime_rate',
        'username', 'password', 'role', 'date_hired', 'prefix', 'title_prefix', 'suffix', 'bdate', 'age',
        'b_place', 'sex', 'civil_status', 'height_cm', 'height_ft', 'weight_kg', 'weight_lb', 'b_type',
        'gsis', 'pagibig', 'philhealth', 'sss', 'tin', 'citizenship', 'telephone', 'mobile',
        'org_email', 'add_block', 'add_street', 'add_village', 'add_brgy', 'add_city', 
        'add_region', 'add_prov', 'add_zcode', 'padd_block', 'padd_street', 'padd_village', 'padd_brgy',
        'padd_city', 'padd_region', 'padd_prov', 'padd_zcode', 'stat_1'
    ]; 

    protected $hidden = [
        'password', // Hide password from JSON output
    ];

    protected $casts = [
        'role' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            $employee->password = Hash::make($employee->password);
        });
    }
}
