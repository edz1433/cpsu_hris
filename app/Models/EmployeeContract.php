<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeContract extends Model
{
    use SoftDeletes;

    const STATUS_ACTIVE = 'active';
    const STATUS_SIGNED = 'signed';
    const STATUS_CANCELLED = 'cancelled';

    protected $connection = 'mysql';
    protected $table = 'employee_contracts';

    protected $fillable = [
        'contract_period_id', 'employee_id', 'reference_no', 'campus_id', 'employee_name', 'position',
        'monthly_rate', 'daily_deduction', 'status', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'monthly_rate' => 'decimal:2',
        'daily_deduction' => 'decimal:2',
    ];

    public function period()
    {
        return $this->belongsTo(ContractPeriod::class, 'contract_period_id');
    }

    /** Read-only lookup; this module never writes to employees. */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Campuses keyed by id (read-only). Camp declares a 'dbcpsuhris' connection that
     * is not configured, so it is queried explicitly on the default connection.
     */
    public static function campuses()
    {
        return Camp::on('mysql')->get(['id', 'campus_name', 'campus_abbr', 'short'])->keyBy('id');
    }

    /** "VICTORIA P. BESANA JR." */
    public static function formatEmployeeName($employee): string
    {
        $middle = trim((string) $employee->mname);
        $parts = [
            trim((string) $employee->fname),
            $middle !== '' ? mb_substr($middle, 0, 1) . '.' : '',
            trim((string) $employee->lname),
            trim((string) $employee->suffix),
        ];

        return mb_strtoupper(preg_replace('/\s+/', ' ', trim(implode(' ', array_filter($parts, 'strlen')))));
    }
}
