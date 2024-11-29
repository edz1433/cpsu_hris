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

    public function dtrSearch(Request $request)
    {
        $guard = $this->getGuard();
        $request->validate([
            'employee' => 'nullable',
            'period' => 'required',
            'date' => 'required|date_format:Y-m',
        ]);

        $empid = $request->employee ?? auth()->guard($guard)->user()->emp_ID;

        $employeeall = null;
        $employeeall = Employee::all();

        $employee = Employee::where('emp_ID', $empid)->first();

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
        ->join('dbcpsupms.offices', 'employees.emp_dept', '=', 'dbcpsupms.offices.id')
        ->select('employees.*', 'dbcpsupms.offices.office_name')
        ->first();

        $supervisor = Employee::where('id', $employee->supervisor)
        ->select('employees.fname', 'employees.lname', 'employees.mname', 'employees.prefix')
        ->first();
        
        $dtrRecords = Dtr::where('emp_ID', $employeeId)
                        ->whereYear('date', $year)
                        ->whereMonth('date', $month)
                        ->get();
        
        $form = ($overtime == 1) ? 'dtr.dtr-pdf-overtime' : 'dtr.dtr-pdf';
    
        $pdf = PDF::loadView($form, [
            'employee' => $employee,
            'supervisor' => $supervisor,
            'dtrRecords' => $dtrRecords,
            'period' => $period,
            'date' => $date,
            'startDate' => $startDate->format('F j'),
            'endDate' => $endDate->format('j'),
            'year' => $year, 
        ])->setPaper('Legal', 'portrait');
    
        return $pdf->stream();
    }

    public function dtrLogs(Request $request)
    {
        $guard = $this->getGuard();
        
        $employeeall = null;
        $employeeall = Employee::all();
  
        $data = null;
    
        if ($request->isMethod('post')) {
            $employeeId = $request->input('employee') ?? auth()->guard($guard)->user()->emp_ID;
            $dateFrom = $request->input('date_from', null);
            $dateTo = $request->input('date_to', null);
    
            $data = [
                "employeeId" => $employeeId,
                "dateFrom" => $dateFrom,
                "dateTo" => $dateTo,
            ];
        }
    
        return view('dtr.log', compact('guard', 'employeeall', 'data'));
    }
    
    public function logDtrView($employeeId, $dateFrom = null, $dateTo = null)
    {
        $guard = $this->getGuard();
        $currentDate = Carbon::now()->toDateString();
    
        $data = [
            "employeeId" => $employeeId,
            "dateFrom" => $dateFrom,
            "dateTo" => $dateTo,
        ];
    
        // Fetch DTR records with necessary conditions
        $dtrRecords = Dtr::join('employees', 'dtrs.emp_ID', '=', 'employees.emp_ID')
            ->when(is_null($dateFrom) && is_null($dateTo), function ($query) use ($currentDate, $employeeId) {
                return $query->whereDate('dtrs.date', $currentDate)
                    ->where('dtrs.emp_ID', $employeeId);
            })
            ->when(!is_null($dateFrom) && !is_null($dateTo), function ($query) use ($employeeId, $dateFrom, $dateTo) {
                return $query->where('dtrs.emp_ID', $employeeId)
                    ->whereBetween('dtrs.date', [$dateFrom, $dateTo]);
            })
            ->select('dtrs.*', 'employees.lname', 'employees.fname', 'employees.suffix')
            ->orderBy('dtrs.date', 'asc')
            ->orderBy('dtrs.time_in', 'asc')
            ->orderBy('dtrs.time_out', 'asc')
            ->get();
    
        $groupedRecords = $dtrRecords->groupBy('emp_ID');
    
        $devices = Fdevice::all();
        $deviceLabels = $devices->pluck('label', 'id')->toArray();
        $deviceCampus = $devices->pluck('camp_id', 'id')->toArray();
    
        $processedLogs = [];
    
        foreach ($groupedRecords as $employeeId => $records) {
            $logSessions = [];
    
            foreach ($records as $record) {
                $timeInArray = explode(',', $record->time_in);
                $deviceInCampArray = explode(',', $record->device_id_in);
    
                foreach ($timeInArray as $index => $timeIn) {
                    $deviceInId = $deviceInCampArray[$index] ?? null;
                    $logSessions[] = [
                        'time' => $timeIn,
                        'type' => 'time_in',
                        'session' => $index == 0 ? 'Morning' : ($index == 1 ? 'Noon' : 'Afternoon'),
                        'date' => $record->date,
                        'lname' => $record->lname,
                        'fname' => $record->fname,
                        'suffix' => $record->suffix,
                        'device_in_label' => $deviceLabels[$deviceInId] ?? 'Unknown',
                        'device_in_campus' => $deviceCampus[$deviceInId] ?? 'Unknown',
                    ];
                }
    
                $timeOutArray = explode(',', $record->time_out);
                $deviceOutCampArray = explode(',', $record->device_id_out);
    
                foreach ($timeOutArray as $index => $timeOut) {
                    $deviceOutId = $deviceOutCampArray[$index] ?? null;
                    $logSessions[] = [
                        'time' => $timeOut,
                        'type' => 'time_out',
                        'session' => $index == 0 ? 'Morning' : ($index == 1 ? 'Afternoon' : 'Evening'),
                        'date' => $record->date,
                        'lname' => $record->lname,
                        'fname' => $record->fname,
                        'suffix' => $record->suffix,
                        'device_out_label' => $deviceLabels[$deviceOutId] ?? 'Unknown',
                        'device_out_campus' => $deviceCampus[$deviceOutId] ?? 'Unknown',
                    ];
                }
            }
    
            // Sort sessions by time
            usort($logSessions, function ($a, $b) {
                return strtotime($a['time']) - strtotime($b['time']);
            });
    
            $processedLogs[$employeeId] = $logSessions;
        }
    
        // Define paper size and margins
        $customPaper = [0, 0, 612, 970];
        $pdf = \PDF::loadView('dtr.logs-pdf', compact('guard', 'dtrRecords', 'processedLogs', 'data'))
            ->setPaper($customPaper, 'portrait')
            ->setOptions([
                'margin-top' => 10,
                'margin-right' => 10,
                'margin-bottom' => 10,
                'margin-left' => 10,
            ])
            ->setCallbacks([
                'before_render' => function ($domPdf) {
                    $domPdf->getCanvas()->page_text(10, 10, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, [0, 0, 0]);
                },
            ]);
    
        return $pdf->stream();
    }
      
    
}
