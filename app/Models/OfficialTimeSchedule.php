<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Date-ranged working-hour override (compressed work week, typhoon schedule,
 * and so on). See database/sql/official_time_schedules.sql for the table.
 *
 * A NULL `empid` means the row applies to every employee. A day whose two
 * columns are both empty is a rest day and is never counted as late.
 */
class OfficialTimeSchedule extends Model
{
    use HasFactory;

    protected $table = 'official_time_schedules';

    protected $fillable = [
        'empid', 'label', 'effective_from', 'effective_to',
        'morn_mon', 'aft_mon', 'morn_tue', 'aft_tue', 'morn_wed', 'aft_wed',
        'morn_thu', 'aft_thu', 'morn_fri', 'aft_fri', 'morn_sat', 'aft_sat',
        'morn_sun', 'aft_sun',
    ];

    /**
     * Kept as plain strings so date comparisons stay simple "Y-m-d" ones.
     */
    protected $casts = [
        'effective_from' => 'string',
        'effective_to' => 'string',
    ];

    public function coversDate($date)
    {
        $day = substr((string) $date, 0, 10);
        $from = substr((string) $this->effective_from, 0, 10);
        $to = $this->effective_to ? substr((string) $this->effective_to, 0, 10) : null;

        return $from <= $day && ($to === null || $to >= $day);
    }
}
