<?php

namespace App\Http\Controllers\Concerns;

use App\Models\OfficialTimeSchedule;
use Carbon\Carbon;

/**
 * Single source of truth for late/undertime computation.
 *
 * Used by the Tardiness module (screen + PDF reports), the DTR form and the
 * employee dashboard so all of them report identical figures for the same DTR
 * rows. Keep the math here; controllers only decide which records to feed in
 * and how to format the result for their own screen.
 *
 * Everything is measured against the employee's OFFICIAL working hours for
 * that specific date, resolved in this order:
 *
 *   1. `official_time_schedules` row for that employee covering the date
 *   2. `official_time_schedules` row with a NULL empid covering the date
 *      (station-wide compressed week, typhoon schedule, ...)
 *   3. the employee's permanent `official_times` row
 *   4. 08:00-12:00 / 13:00-17:00 on Mon-Fri, nothing on Sat/Sun
 *
 * A half-day with no scheduled hours is never counted. A half-day that IS
 * scheduled but whose in/out pair is incomplete is charged in full, because
 * there is no proof the employee was present for it.
 */
trait CalculatesTardiness
{
    /** @var array<string, \Illuminate\Support\Collection> */
    private $scheduleOverrideCache = [];

    private const WEEKDAY_KEYS = ['mon', 'tue', 'wed', 'thu', 'fri'];

    private function defaultSchedule()
    {
        return [
            'mornin' => '08:00',
            'mornout' => '12:00',
            'aftin' => '13:00',
            'aftout' => '17:00',
        ];
    }

    /**
     * Normalize punches to whole minutes so seconds and milliseconds never
     * affect tardiness or undertime totals.
     */
    private function normalizeClockMinute($time)
    {
        if (!$time) {
            return null;
        }

        $time = trim((string) $time);

        try {
            return Carbon::parse($time)->format('H:i');
        } catch (\Exception $e) {
            if (preg_match('/\b(\d{1,2}):(\d{2})\b/', $time, $matches)) {
                return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
            }
        }

        return null;
    }

    private function clockToMinutes($time)
    {
        $minuteTime = $this->normalizeClockMinute($time);

        if (!$minuteTime) {
            return null;
        }

        [$hours, $minutes] = array_map('intval', explode(':', $minuteTime));

        return ($hours * 60) + $minutes;
    }

    private function minutesAfter($time, $limit)
    {
        $actual = $this->clockToMinutes($time);
        $expected = $this->clockToMinutes($limit);

        if ($actual === null || $expected === null || $actual <= $expected) {
            return 0;
        }

        return $actual - $expected;
    }

    private function minutesBefore($time, $limit)
    {
        $actual = $this->clockToMinutes($time);
        $expected = $this->clockToMinutes($limit);

        if ($actual === null || $expected === null || $actual >= $expected) {
            return 0;
        }

        return $expected - $actual;
    }

    /**
     * Split "07:30:00-11:30:00" into a start/end pair. Returns [null, null]
     * when the value is missing or unusable, which means "no work scheduled".
     */
    private function parseOfficialRange($range, $fallbackStart = null, $fallbackEnd = null)
    {
        $times = $range ? array_map('trim', explode('-', (string) $range)) : [];

        $start = $this->normalizeClockMinute($times[0] ?? null) ?: $fallbackStart;
        $end = $this->normalizeClockMinute($times[1] ?? null) ?: $fallbackEnd;

        if ($start === null || $end === null) {
            return [null, null];
        }

        // Guard against reversed or zero-length ranges in the stored data
        // (there are rows like "07:00:00-00:00:00"), which would otherwise
        // produce a negative half-day span.
        if ($this->clockToMinutes($end) <= $this->clockToMinutes($start)) {
            return [null, null];
        }

        return [$start, $end];
    }

    /**
     * Every override row that could apply to this employee, newest first.
     * Falls back to an empty set when the table has not been created yet, so
     * the app keeps working before the SQL in database/sql/ is run.
     */
    private function scheduleOverridesFor($empId)
    {
        $key = $empId === null ? '*' : (string) $empId;

        if (!array_key_exists($key, $this->scheduleOverrideCache)) {
            try {
                $this->scheduleOverrideCache[$key] = OfficialTimeSchedule::query()
                    ->where(function ($query) use ($empId) {
                        $query->whereNull('empid');

                        if ($empId !== null) {
                            $query->orWhere('empid', $empId);
                        }
                    })
                    ->orderByDesc('effective_from')
                    ->orderByDesc('id')
                    ->get();
            } catch (\Throwable $e) {
                $this->scheduleOverrideCache[$key] = collect();
            }
        }

        return $this->scheduleOverrideCache[$key];
    }

    /**
     * The override that wins for this employee on this date: an employee
     * specific row beats a station-wide one, and newer beats older.
     */
    private function resolveScheduleOverride($empId, $date)
    {
        $covering = $this->scheduleOverridesFor($empId)
            ->filter(fn ($row) => $row->coversDate($date));

        if ($covering->isEmpty()) {
            return null;
        }

        if ($empId !== null) {
            $specific = $covering->first(fn ($row) => (string) $row->empid === (string) $empId);

            if ($specific) {
                return $specific;
            }
        }

        return $covering->first(fn ($row) => $row->empid === null || $row->empid === '');
    }

    /**
     * Build a schedule array from a morning and an afternoon range. Returns
     * null when neither half has scheduled hours (a rest day).
     */
    private function scheduleFromRanges($morningRange, $afternoonRange, array $fallback = null)
    {
        [$mornIn, $mornOut] = $this->parseOfficialRange(
            $morningRange,
            $fallback['mornin'] ?? null,
            $fallback['mornout'] ?? null
        );
        [$aftIn, $aftOut] = $this->parseOfficialRange(
            $afternoonRange,
            $fallback['aftin'] ?? null,
            $fallback['aftout'] ?? null
        );

        if ($mornIn === null && $aftIn === null) {
            return null;
        }

        return [
            'mornin' => $mornIn,
            'mornout' => $mornOut,
            'aftin' => $aftIn,
            'aftout' => $aftOut,
        ];
    }

    /**
     * Official working hours for one employee on one date, or null when no
     * work is scheduled that day (weekend, rest day, declared holiday-style
     * override). Never invents hours for a day the employee is not expected.
     */
    private function officialScheduleForDate($officialTime, $date, $empId = null)
    {
        $dayKey = strtolower(Carbon::parse($date)->format('D'));
        $empId = $empId ?: ($officialTime->empid ?? null);

        $override = $this->resolveScheduleOverride($empId, $date);

        if ($override) {
            return $this->scheduleFromRanges(
                $override->{'morn_' . $dayKey} ?? null,
                $override->{'aft_' . $dayKey} ?? null
            );
        }

        $isWeekday = in_array($dayKey, self::WEEKDAY_KEYS, true);

        if ($officialTime && $isWeekday) {
            return $this->scheduleFromRanges(
                $officialTime->{'morn_' . $dayKey},
                $officialTime->{'aft_' . $dayKey},
                $this->defaultSchedule()
            );
        }

        return $isWeekday ? $this->defaultSchedule() : null;
    }

    private function dtrTimes($value)
    {
        if (!$value) {
            return collect();
        }

        return collect(explode(',', $value))
            ->map(fn ($time) => $this->normalizeClockMinute($time))
            ->filter()
            ->unique()
            ->sortBy(fn ($time) => $this->clockToMinutes($time))
            ->values();
    }

    /**
     * Where a punch stops belonging to the morning, taken from the schedule.
     *
     * The two boundaries differ on purpose. Once the morning is over you can
     * no longer arrive *for* the morning, so a time-in from `mornout` onwards
     * is the lunch return. Symmetrically, the afternoon has not started until
     * `aftin`, so a time-out up to then is still the morning departure. Using
     * the midpoint of the break for both would misfile an employee who comes
     * back early (11:20 against an 11:00/12:00 break).
     */
    private function punchBoundaries(array $schedule)
    {
        $morningOut = $this->clockToMinutes($schedule['mornout'] ?? null);
        $afternoonIn = $this->clockToMinutes($schedule['aftin'] ?? null);
        $endOfDay = (24 * 60) + 1;

        // Half-day schedules: every punch belongs to the half that exists.
        if ($afternoonIn === null) {
            return ['in' => $endOfDay, 'out' => $endOfDay];
        }

        if ($morningOut === null) {
            return ['in' => 0, 'out' => 0];
        }

        return ['in' => $morningOut, 'out' => $afternoonIn];
    }

    /**
     * Assign the day's punches to the morning and the afternoon.
     *
     * Split on the scheduled lunch break rather than on a fixed tolerance, so
     * arriving late or leaving early never causes a punch to be thrown away.
     * Dropping a punch matters now that an unpaired half-day is charged in
     * full: a 45-minute late lunch return must cost 45 minutes, not the whole
     * afternoon.
     */
    private function dailyWorkPunches($dtr, $schedule = null)
    {
        $schedule = $schedule ?: $this->defaultSchedule();
        $boundary = $this->punchBoundaries($schedule);

        $timeIns = $this->dtrTimes(optional($dtr)->time_in);
        $timeOuts = $this->dtrTimes(optional($dtr)->time_out);

        $morningIns = $timeIns->filter(fn ($t) => $this->clockToMinutes($t) < $boundary['in'])->values();
        $afternoonIns = $timeIns->filter(fn ($t) => $this->clockToMinutes($t) >= $boundary['in'])->values();
        $morningOuts = $timeOuts->filter(fn ($t) => $this->clockToMinutes($t) <= $boundary['out'])->values();
        $afternoonOuts = $timeOuts->filter(fn ($t) => $this->clockToMinutes($t) > $boundary['out'])->values();

        return [
            // First arrival of the morning, last departure before lunch.
            'am_in' => $morningIns->first(),
            'am_out' => $morningOuts->last(),
            // First return after lunch, last departure of the day.
            'pm_in' => $afternoonIns->first(),
            'pm_out' => $afternoonOuts->last(),
            'time_in_count' => $morningIns->count() + $afternoonIns->count(),
            'time_out_count' => $morningOuts->count() + $afternoonOuts->count(),
        ];
    }

    private function emptyTardinessSummary()
    {
        return [
            'morning_late_minutes' => 0,
            'morning_late_days' => 0,
            'afternoon_late_minutes' => 0,
            'afternoon_late_days' => 0,
            'morning_undertime_minutes' => 0,
            'morning_undertime_days' => 0,
            'afternoon_undertime_minutes' => 0,
            'afternoon_undertime_days' => 0,
        ];
    }

    /**
     * One half-day, measured against its own scheduled hours.
     *
     * Complete in + out  -> real late and undertime minutes.
     * Anything else      -> the whole scheduled half-day is charged as late,
     *                       because attendance for it cannot be proven.
     * Nothing scheduled  -> not counted at all.
     *
     * @return array{0:int,1:int,2:bool} [late, undertime, chargedAsAbsent]
     */
    private function halfDayTardiness($in, $out, $scheduledIn, $scheduledOut)
    {
        $start = $this->clockToMinutes($scheduledIn);
        $end = $this->clockToMinutes($scheduledOut);

        if ($start === null || $end === null) {
            return [0, 0, false];
        }

        if ($in !== null && $out !== null) {
            return [
                $this->minutesAfter($in, $scheduledIn),
                $this->minutesBefore($out, $scheduledOut),
                false,
            ];
        }

        return [max(0, $end - $start), 0, true];
    }

    private function calculateDayTardiness($dtr, $officialTime)
    {
        $schedule = $this->officialScheduleForDate(
            $officialTime,
            $dtr->date,
            $dtr->emp_ID ?? null
        );

        if ($schedule === null) {
            return [
                'schedule' => null,
                'punches' => $this->dailyWorkPunches($dtr),
                'no_schedule' => true,
                'morning_charged' => false,
                'afternoon_charged' => false,
                'time_in_review' => false,
                'time_out_review' => false,
                'morning_late_minutes' => 0,
                'afternoon_late_minutes' => 0,
                'morning_undertime_minutes' => 0,
                'afternoon_undertime_minutes' => 0,
            ];
        }

        $punches = $this->dailyWorkPunches($dtr, $schedule);

        [$morningLate, $morningUndertime, $morningCharged] = $this->halfDayTardiness(
            $punches['am_in'], $punches['am_out'], $schedule['mornin'], $schedule['mornout']
        );
        [$afternoonLate, $afternoonUndertime, $afternoonCharged] = $this->halfDayTardiness(
            $punches['pm_in'], $punches['pm_out'], $schedule['aftin'], $schedule['aftout']
        );

        return [
            'schedule' => $schedule,
            'punches' => $punches,
            'no_schedule' => false,
            'morning_charged' => $morningCharged,
            'afternoon_charged' => $afternoonCharged,
            'time_in_review' => $morningCharged || $afternoonCharged,
            'time_out_review' => $morningCharged || $afternoonCharged,
            'morning_late_minutes' => $morningLate,
            'afternoon_late_minutes' => $afternoonLate,
            'morning_undertime_minutes' => $morningUndertime,
            'afternoon_undertime_minutes' => $afternoonUndertime,
        ];
    }

    private function summarizeDtrRecords($dtrRecords, $officialTime)
    {
        $summary = $this->emptyTardinessSummary();

        foreach ($dtrRecords as $dtr) {
            $day = $this->calculateDayTardiness($dtr, $officialTime);

            foreach (['morning_late', 'afternoon_late', 'morning_undertime', 'afternoon_undertime'] as $key) {
                $minutesKey = $key . '_minutes';
                $daysKey = $key . '_days';
                $minutes = $day[$minutesKey];

                $summary[$minutesKey] += $minutes;
                $summary[$daysKey] += $minutes > 0 ? 1 : 0;
            }
        }

        return $summary;
    }

    /**
     * Collapse the AM/PM breakdown used by the Tardiness reports into the
     * whole-day totals the dashboard and the DTR form show. Day counts are per
     * calendar day, so being late in both halves counts once.
     */
    private function dtrTardinessTotals($dtrRecords, $officialTime = null)
    {
        $totals = [
            'late_minutes' => 0,
            'undertime_minutes' => 0,
            'late_days' => 0,
            'undertime_days' => 0,
        ];

        foreach ($dtrRecords as $dtr) {
            $day = $this->calculateDayTardiness($dtr, $officialTime);

            $lateMinutes = $day['morning_late_minutes'] + $day['afternoon_late_minutes'];
            $undertimeMinutes = $day['morning_undertime_minutes'] + $day['afternoon_undertime_minutes'];

            $totals['late_minutes'] += $lateMinutes;
            $totals['undertime_minutes'] += $undertimeMinutes;
            $totals['late_days'] += $lateMinutes > 0 ? 1 : 0;
            $totals['undertime_days'] += $undertimeMinutes > 0 ? 1 : 0;
        }

        return $totals;
    }
}
