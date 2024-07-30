<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Dtr;
use App\Models\Fdevice;
use Carbon\Carbon;
use PDF;

class DtrController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function dtrRead()
    {
        $guard = $this->getGuard();
        if(auth()->guard($guard)->user()->role == "employee"){
            $empid = auth()->guard($guard)->user()->emp_ID;
            $employeeall = Employee::where('emp_ID', $empid)->first();
        }else{
            $employeeall = Employee::all();
        }
        return view('dtr.dtr', compact('guard', 'employeeall'));
    }

    public function dtrLogs(Request $request)
    {
        $guard = $this->getGuard();
        $currentDate = Carbon::now()->toDateString();
        
        if ($request->isMethod('get')) {
            $dtrRecords = Dtr::join('employees', 'dtrs.emp_ID', '=', 'employees.emp_ID')
                ->whereDate('dtrs.date', $currentDate)
                ->select('dtrs.*', 'employees.lname', 'employees.fname', 'employees.suffix')
                ->orderBy('dtrs.date', 'asc')
                ->orderBy('dtrs.time_in', 'asc')
                ->orderBy('dtrs.time_out', 'asc')
                ->get();
        }else{
            $employeeId = $request->input('employee');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
    
            $dtrRecords = Dtr::join('employees', 'dtrs.emp_ID', '=', 'employees.emp_ID')
                ->when($employeeId, function ($query, $employeeId) {
                    return $query->where('dtrs.emp_ID', $employeeId);
                })
                ->when($dateFrom && $dateTo, function ($query) use ($dateFrom, $dateTo) {
                    return $query->whereBetween('dtrs.date', [$dateFrom, $dateTo]);
                })
                ->select('dtrs.*', 'employees.lname', 'employees.fname', 'employees.suffix')
                ->orderBy('dtrs.date', 'asc')
                ->orderBy('dtrs.time_in', 'asc')
                ->orderBy('dtrs.time_out', 'asc')
                ->get();
        }
        // Group records by employee ID
        $groupedRecords = $dtrRecords->groupBy('emp_ID');
    
        // Fetch devices and create a device ID to label and campus mapping
        $devices = Fdevice::all();
        $deviceLabels = $devices->pluck('label', 'id')->toArray(); // Use 'id' as key
        $deviceCampus = $devices->pluck('camp_id', 'id')->toArray(); // Use 'id' as key
    
        $processedLogs = [];
    
        foreach ($groupedRecords as $employeeId => $records) {
            $logSessions = [];
    
            foreach ($records as $record) {
                // Handle time_in
                $timeInArray = explode(',', $record->time_in);
                $deviceInCampArray = explode(',', $record->device_id_in); // Added line
    
                foreach ($timeInArray as $index => $timeIn) {
                    $deviceInId = isset($deviceInCampArray[$index]) ? $deviceInCampArray[$index] : null;
                    $logSessions[] = [
                        'time' => $timeIn,
                        'type' => 'time_in',
                        'session' => $index == 0 ? 'Morning' : ($index == 1 ? 'Noon' : 'Afternoon'),
                        'date' => $record->date,
                        'lname' => $record->lname,
                        'fname' => $record->fname,
                        'suffix' => $record->suffix,
                        'device_in_label' => $deviceLabels[$deviceInId] ?? 'Unknown',
                        'device_in_campus' => $deviceCampus[$deviceInId] ?? 'Unknown'
                    ];
                }
    
                // Handle time_out
                $timeOutArray = explode(',', $record->time_out);
                $deviceOutCampArray = explode(',', $record->device_id_out); // Added line
    
                foreach ($timeOutArray as $index => $timeOut) {
                    $deviceOutId = isset($deviceOutCampArray[$index]) ? $deviceOutCampArray[$index] : null;
                    $logSessions[] = [
                        'time' => $timeOut,
                        'type' => 'time_out',
                        'session' => $index == 0 ? 'Morning' : ($index == 1 ? 'Afternoon' : 'Evening'),
                        'date' => $record->date,
                        'lname' => $record->lname,
                        'fname' => $record->fname,
                        'suffix' => $record->suffix,
                        'device_out_label' => $deviceLabels[$deviceOutId] ?? 'Unknown',
                        'device_out_campus' => $deviceCampus[$deviceOutId] ?? 'Unknown'
                    ];
                }
            }
    
            usort($logSessions, function($a, $b) {
                return strtotime($a['time']) - strtotime($b['time']);
            });
    
            $processedLogs[$employeeId] = $logSessions;
        }
    

        if(auth()->guard($guard)->user()->role == "employee"){
            $empid = auth()->guard($guard)->user()->emp_ID;
            $employeeall = Employee::where('emp_ID', $empid)->first();
        }else{
            $employeeall = Employee::all();
        }
        return view('dtr.log', compact('guard', 'employeeall', 'dtrRecords', 'processedLogs'));
    }

    public function dtrSearch(Request $request)
    {
        $guard = $this->getGuard();
        if(auth()->guard($guard)->user()->role == "employee"){
            $employeeall = Employee::where('emp_ID', $request->employee)->first();
        }else{
            $employeeall = Employee::all();
        }
        $employee = Employee::where('emp_ID', $request->employee)->first();
        // dd($employeeall);
        $request->validate([
            'employee' => 'required',
            'period' => 'required',
            'date' => 'required|date_format:Y-m',
        ]);

        $employ = $request->input('employee');
        $period = $request->input('period');
        $date = $request->input('date');
        $overtime = $request->input('overtime');

        $dtr = Dtr::where('emp_ID', $employ)
                ->whereYear('date', substr($date, 0, 4)) 
                ->whereMonth('date', substr($date, 5, 2))
                ->get();

        return view('dtr.dtr', compact('guard', 'dtr', 'employeeall', 'employee', 'period', 'date', 'overtime'));
    }

    public function dtrPdf(Request $request)
    {
        $request->validate([
            'employee' => 'required',
            'period' => 'required',
            'date' => 'required|date_format:Y-m',
        ]);
    
        $employeeId = $request->input('employee');
        $period = $request->input('period');
        $date = $request->input('date');
        $overtime = $request->input('overtime');
    
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);

         // Calculate the start and end dates based on the period
        $startDate = null;
        $endDate = null;

        switch ($period) {
            case 1:
                $startDate = Carbon::createFromDate($year, $month, 1);
                $endDate = Carbon::createFromDate($year, $month, 15);
                break;
            case 2:
                $startDate = Carbon::createFromDate($year, $month, 16);
                $endDate = Carbon::createFromDate($year, $month)->endOfMonth();
                break;
            case 3:
                $startDate = Carbon::createFromDate($year, $month, 1);
                $endDate = Carbon::createFromDate($year, $month)->endOfMonth();
                break;
        }
    
        $employee = Employee::where('emp_ID', $employeeId)
        ->join('cpsupms.offices', 'employees.emp_dept', '=', 'cpsupms.offices.id')
        ->select('employees.*', 'cpsupms.offices.office_name')
        ->first();
    
        $dtrRecords = Dtr::where('emp_ID', $employeeId)
                        ->whereYear('date', $year)
                        ->whereMonth('date', $month)
                        ->get();
        
        $form = ($overtime == 1) ? 'dtr.dtr-pdf-overtime' : 'dtr.dtr-pdf';
    
        $pdf = PDF::loadView($form, [
            'employee' => $employee,
            'dtrRecords' => $dtrRecords,
            'period' => $period,
            'date' => $date,
            'startDate' => $startDate->format('F j'),
            'endDate' => $endDate->format('j'),
            'year' => $year, 
        ])->setPaper('Legal', 'portrait');
    
        return $pdf->stream();
    }
}
