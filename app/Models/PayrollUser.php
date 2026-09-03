<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollUser extends Model
{
    protected $connection = 'payroll';
    protected $table = 'users';
    protected $fillable = [
        'campus_id', 'name', 'email', 'password', 'role_id', 'is_active'
    ];
}
