<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollEmployee extends Model
{
    protected $connection = 'payroll';
    protected $table = 'employees';

    protected $fillable = [
        'employee_no', 'full_name', 'designation', 'campus_id', 'office_id',
        'status_id', 'salary_grade', 'monthly_salary', 'rate_per_day',
        'rate_per_hour', 'rate_per_minute', 'is_active',
    ];

    protected $casts = [
        'monthly_salary' => 'decimal:2',
        'rate_per_day' => 'decimal:2',
        'rate_per_hour' => 'decimal:2',
        'rate_per_minute' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    /**
     * Payroll keys employees by employee_no, which holds the HRIS employees.emp_ID value.
     */
    public function scopeForEmpId($query, $empId)
    {
        return $query->where('employee_no', $empId);
    }
}
