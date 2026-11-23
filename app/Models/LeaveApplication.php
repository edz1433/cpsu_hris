<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplication extends Model
{
    // Compensatory Time-Off: filed in hours against the employee's COC balance
    // (employees.coc_leave), with its dates in leave_cto_dates.
    public const TYPE_CTO = 16;

    protected $fillable = [
        'cto_hours',
        'coc_balance',
        'id',
        'transnum',
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
        'less_vl',
        'less_sl',
        'recommend',
        'emp_esign',
        'emp_signed_at',
        'supervisor',
        'oic',
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
        'remarks_details1',
        'remarks_details2',
        'department',
        'date_filing',
        'day_wpay',
        'earn',
        'less',
        'balance',
        'status',
        'gen_app',
        'as_of',
        'holiday'
    ];

    public function office()
    {
        return $this->belongsTo(Office::class, 'department', 'id');
    }
    
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'empid', 'emp_ID');
    }

    public function ctoDates()
    {
        return $this->hasMany(LeaveCtoDate::class)->orderBy('cto_date');
    }

    public function isCto(): bool
    {
        return (int) $this->leave_type === self::TYPE_CTO;
    }

    // "8" instead of "8.00", "4.5" instead of "4.50".
    public static function formatHours($hours): string
    {
        return rtrim(rtrim(number_format((float) $hours, 2, '.', ''), '0'), '.');
    }

    /**
     * The "As of" date on the leave form: the last day of the month before the
     * leave starts. A date range that can't be read keeps the stored as_of.
     */
    public function certificationAsOf(): ?\Carbon\Carbon
    {
        if (preg_match('/^\s*(\d{4})-(\d{2})-(\d{2})/', (string) $this->date_range, $start)
            && checkdate((int) $start[2], (int) $start[3], (int) $start[1])) {
            return \Carbon\Carbon::create((int) $start[1], (int) $start[2], 1)->subDay();
        }

        try {
            return $this->as_of ? \Carbon\Carbon::parse($this->as_of) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Who signs this application at the supervisor step (employees.id).
     * An OIC takes over the signing while assigned to the office.
     *
     * @return array{id:int, supervisor:int, is_oic:bool}
     */
    public function supervisorSignatory(): array
    {
        $employee = Employee::where('emp_ID', $this->empid)->first();
        $officeid = $this->department ?: optional($employee)->emp_dept;
        $office = $officeid ? Office::find($officeid) : null;
        $supervisor = (int) ($this->supervisor ?: optional($employee)->supervisor);

        // An office with no OIC, or one pointing at an employee who no longer
        // exists, leaves the immediate supervisor as the signatory.
        $oicid = ($office && $office->oic_id && Employee::where('id', $office->oic_id)->exists())
            ? (int) $office->oic_id
            : null;

        return [
            'id' => $oicid ?: $supervisor,
            'supervisor' => $supervisor,
            'is_oic' => (bool) $oicid,
        ];
    }

    // public function leaveApplications()
    // {
    //     return $this->hasMany(LeaveApplication::class, 'empid', 'emp_ID');
    // }

}
