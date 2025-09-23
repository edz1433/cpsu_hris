<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'first_name',
        'middle_name',
        'last_name',
        'age',
        'sex',
        'mobile',
        'email',
        'address',
        'education',
        'elevel',
        'eyear',
        'eligibility',
        'pds',
        'wes',
        'ilf',
        'resume',
        'tor',
        'coe',
        'cot',
        'application_date',
        'status'
    ];
}
