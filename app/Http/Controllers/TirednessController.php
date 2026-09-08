<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Dtr;
use App\Models\Fdevice;
use App\Models\OfficialTime;
use Carbon\Carbon;
use PDF;
use App\Http\Controllers\Concerns\CalculatesTardiness;

class TirednessController extends Controller
{
    use CalculatesTardiness;

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
                'no_schedule' => $calculation['no_schedule'] ?? false,
                'morning_charged' => $calculation['morning_charged'] ?? false,
                'afternoon_charged' => $calculation['afternoon_charged'] ?? false,
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
