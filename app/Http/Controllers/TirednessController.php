<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Dtr;
use App\Models\Fdevice;
use App\Models\OfficialTime;
use Carbon\Carbon;
use PDF;

class TirednessController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function readTiredness(Request $request)
    {
        $guard = $this->getGuard();
        $employeeall = Employee::orderBy('lname', 'asc')->get();
        $employee = null;
        $month = null;
        $employeeId = null;
    
        if ($request->isMethod('post')) {
            if ($request->has('employee') && $request->has('month')) {
                $employeeId = (string) $request->employee;
                $employee = $employeeId !== '0' ? Employee::where('emp_ID', $employeeId)->first() : null;
                $month = $request->month;
            }
        }
    
        return view('tiredeness.tiredeness', compact('guard', 'employee', 'employeeall', 'employeeId', 'month'));
    }

    private function defaultSchedule()
    {
        return [
            'mornin' => '08:00',
            'mornout' => '12:00',
            'aftin' => '13:00',
            'aftout' => '17:00',
        ];
    }

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

    private function parseOfficialRange($range, $fallbackStart, $fallbackEnd)
    {
        $times = $range ? array_map('trim', explode('-', $range)) : [];

        return [
            $this->normalizeClockMinute($times[0] ?? null) ?: $fallbackStart,
            $this->normalizeClockMinute($times[1] ?? null) ?: $fallbackEnd,
        ];
    }

    private function officialScheduleForDate($officialTime, $date)
    {
        $default = $this->defaultSchedule();
        $day = strtolower(Carbon::parse($date)->format('D'));
        $dayMap = [
            'mon' => ['morn_mon', 'aft_mon'],
            'tue' => ['morn_tue', 'aft_tue'],
            'wed' => ['morn_wed', 'aft_wed'],
            'thu' => ['morn_thu', 'aft_thu'],
            'fri' => ['morn_fri', 'aft_fri'],
        ];

        if (!$officialTime || !isset($dayMap[$day])) {
            return $default;
        }

        [$morningField, $afternoonField] = $dayMap[$day];
        [$mornIn, $mornOut] = $this->parseOfficialRange($officialTime->{$morningField}, $default['mornin'], $default['mornout']);
        [$aftIn, $aftOut] = $this->parseOfficialRange($officialTime->{$afternoonField}, $default['aftin'], $default['aftout']);

        return [
            'mornin' => $mornIn,
            'mornout' => $mornOut,
            'aftin' => $aftIn,
            'aftout' => $aftOut,
        ];
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

    private function dailyWorkPunches($dtr, $schedule)
    {
        $timeIns = $this->dtrTimes(optional($dtr)->time_in);
        $timeOuts = $this->dtrTimes(optional($dtr)->time_out);
        $latestUsefulTimeIn = $this->clockToMinutes($schedule['aftin']) + 30;
        $earliestUsefulTimeOut = $this->clockToMinutes($schedule['mornout']) - 60;

        $dailyTimeIns = $timeIns
            ->filter(fn ($time) => $this->clockToMinutes($time) <= $latestUsefulTimeIn)
            ->values();

        $dailyTimeOuts = $timeOuts
            ->filter(fn ($time) => $this->clockToMinutes($time) >= $earliestUsefulTimeOut)
            ->values();

        return [
            'am_in' => $dailyTimeIns->first(),
            'am_out' => $dailyTimeOuts->first(),
            'pm_in' => $dailyTimeIns->count() >= 2 ? $dailyTimeIns->last() : null,
            'pm_out' => $dailyTimeOuts->count() >= 2 ? $dailyTimeOuts->last() : null,
            'time_in_count' => $dailyTimeIns->count(),
            'time_out_count' => $dailyTimeOuts->count(),
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

    private function calculateDayTardiness($dtr, $officialTime)
    {
        $schedule = $this->officialScheduleForDate($officialTime, $dtr->date);
        $punches = $this->dailyWorkPunches($dtr, $schedule);
        $hasCompleteTimeIns = $punches['time_in_count'] >= 2;
        $hasCompleteTimeOuts = $punches['time_out_count'] >= 2;

        return [
            'schedule' => $schedule,
            'punches' => $punches,
            'time_in_review' => !$hasCompleteTimeIns,
            'time_out_review' => !$hasCompleteTimeOuts,
            'morning_late_minutes' => $hasCompleteTimeIns ? $this->minutesAfter($punches['am_in'], $schedule['mornin']) : 0,
            'afternoon_late_minutes' => $hasCompleteTimeIns ? $this->minutesAfter($punches['pm_in'], $schedule['aftin']) : 0,
            'morning_undertime_minutes' => $hasCompleteTimeOuts ? $this->minutesBefore($punches['am_out'], $schedule['mornout']) : 0,
            'afternoon_undertime_minutes' => $hasCompleteTimeOuts ? $this->minutesBefore($punches['pm_out'], $schedule['aftout']) : 0,
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

    private function buildMonthlyRows($dtrRecords, $officialTime, $year, $monthNumber)
    {
        $month = Carbon::createFromDate((int) $year, (int) $monthNumber, 1);
        $recordsByDate = $dtrRecords->keyBy('date');
        $summary = $this->emptyTardinessSummary();
        $rows = collect();

        for ($day = 1; $day <= $month->daysInMonth; $day++) {
            $date = $month->copy()->day($day);
            $rowData = $recordsByDate->get($date->format('Y-m-d'));
            $calculation = null;

            if ($rowData) {
                $calculation = $this->calculateDayTardiness($rowData, $officialTime);

                foreach (['morning_late', 'afternoon_late', 'morning_undertime', 'afternoon_undertime'] as $key) {
                    $minutesKey = $key . '_minutes';
                    $summary[$minutesKey] += $calculation[$minutesKey];
                    $summary[$key . '_days'] += $calculation[$minutesKey] > 0 ? 1 : 0;
                }
            }

            $rows->push([
                'day' => $day,
                'date' => $date->format('Y-m-d'),
                'day_of_week' => $date->format('l'),
                'has_record' => (bool) $rowData,
                'time_in_review' => $calculation['time_in_review'] ?? false,
                'time_out_review' => $calculation['time_out_review'] ?? false,
                'morning_late_minutes' => $calculation['morning_late_minutes'] ?? null,
                'afternoon_late_minutes' => $calculation['afternoon_late_minutes'] ?? null,
                'morning_undertime_minutes' => $calculation['morning_undertime_minutes'] ?? null,
                'afternoon_undertime_minutes' => $calculation['afternoon_undertime_minutes'] ?? null,
            ]);
        }

        return [$rows, $summary];
    }

    private function formatMinutes($minutes)
    {
        $minutes = (int) $minutes;

        return sprintf('%02d:%02d', floor($minutes / 60), $minutes % 60);
    }
    
    public function pdfTirednes($employeeId, $month)
    {
        $year = (int) explode('-', $month)[0];
        $guard = $this->getGuard();
        $dailyRows = collect();
        $summary = $this->emptyTardinessSummary();
        $formattedSummary = [];
    
        $monthNumber = (int) date('m', strtotime($month));
    
        if((string) $employeeId === '0'){
            $monthlyDtrs = Dtr::whereYear('date', $year)
                ->whereMonth('date', $monthNumber)
                ->get()
                ->groupBy('emp_ID');

            $employees = Employee::whereIn('emp_ID', $monthlyDtrs->keys())
                ->orderBy('lname', 'asc')
                ->get();

            $officialTimesByEmployee = OfficialTime::whereIn('empid', $employees->pluck('emp_ID'))
                ->get()
                ->keyBy('empid');

            $dtrRecords = $employees->map(function ($employee) use ($monthlyDtrs, $officialTimesByEmployee) {
                $summary = $this->summarizeDtrRecords(
                    $monthlyDtrs->get($employee->emp_ID, collect()),
                    $officialTimesByEmployee->get($employee->emp_ID)
                );

                return (object) array_merge([
                    'lname' => $employee->lname,
                    'prefix' => $employee->prefix,
                    'fname' => $employee->fname,
                    'mname' => $employee->mname,
                    'morning_late_time' => $this->formatMinutes($summary['morning_late_minutes']),
                    'afternoon_late_time' => $this->formatMinutes($summary['afternoon_late_minutes']),
                    'morning_undertime_time' => $this->formatMinutes($summary['morning_undertime_minutes']),
                    'afternoon_undertime_time' => $this->formatMinutes($summary['afternoon_undertime_minutes']),
                ], $summary);
            });

            $form = 'tiredeness.tiredeness-pdf';
        }else{
            $dtrRecords = Dtr::where('emp_ID', $employeeId)
                ->whereYear('date', $year)
                ->whereMonth('date', $monthNumber)
                ->get();

            $officialtimes = OfficialTime::where('empid', '=', $employeeId)->first();
            [$dailyRows, $summary] = $this->buildMonthlyRows($dtrRecords, $officialtimes, $year, $monthNumber);
            $formattedSummary = collect($summary)
                ->mapWithKeys(fn ($minutes, $key) => str_ends_with($key, '_minutes') ? [$key => $this->formatMinutes($minutes)] : [$key => $minutes])
                ->all();

            $form = 'tiredeness.tiredeness-pdf1';
        }
        
        $pdf = PDF::loadView($form, compact('dtrRecords', 'dailyRows', 'summary', 'formattedSummary', 'monthNumber', 'year'))->setPaper('Legal', 'portrait');
        
        return $pdf->stream();
    }
    
    
    
}
