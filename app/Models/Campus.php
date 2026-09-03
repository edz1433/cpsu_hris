<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    use HasFactory;

    protected $connection = 'payroll';

    /**
     * Payroll v2 renamed campus_name/campus_abbr to name/code. HRIS views still read
     * the old names, so expose them as accessors instead of touching every blade.
     */
    protected $appends = ['campus_name', 'campus_abbr'];

    public function getCampusNameAttribute()
    {
        return $this->attributes['name'] ?? null;
    }

    public function getCampusAbbrAttribute()
    {
        return $this->attributes['code'] ?? null;
    }
}
