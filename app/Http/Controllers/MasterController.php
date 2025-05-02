<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Campus;
use App\Models\User;
use App\Models\DocuFolder;
use App\Models\Dtr;
use App\Models\LeaveApplication;
use App\Models\Eligibility;
use App\Models\WorkExperience;
use App\Models\LearningDev; 
use App\Models\VoluntaryWork;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class MasterController extends Controller
{

    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function dashboard()
    {
        $guard = $this->getGuard();
        $userCount = User::all();
        $campCount = Campus::all();
        $dtrCount = Dtr::whereDate('date', Carbon::now('Asia/Manila')->toDateString())->count();
        $chartEmployee = Employee::where('stat_1', 1)->get();

        $leaveappCount = LeaveApplication::where('emp_esign', '=', 0)->where('history', 1)->where('status', 1)->count('empid');
        $eliCount = Eligibility::where('status', 0)->count();
        $workexpCount = WorkExperience::where('status', 0)->count();
        $learDevCount = LearningDev::where('status', 0)->count();
        $volWorkCount = VoluntaryWork::where('status', 0)->count();

        $totalEmployees = $chartEmployee->count();
        $empStatuses = [1, 2, 3, 4];

        // Calculate percentage for each emp_status and ensure the correct order
        $empStatusPercentages = collect($empStatuses)->mapWithKeys(function ($status) use ($chartEmployee, $totalEmployees) {
            $count = $chartEmployee->where('emp_status', $status)->count();
            $percentage = $totalEmployees > 0 ? ($count / $totalEmployees) * 100 : 0;
            return [$status => ['count' => $count, 'percentage' => $percentage]];
        });
        
        $offCount = Office::all();
    
        if (\Auth::guard('web')->check()) {
            $today = Carbon::now();
            $currentYear = $today->year;

            $today = Carbon::today();
            
            $upcomingBirthdays = Employee::whereNotNull('employees.bdate')
            ->join('dbcpsupms.offices', 'employees.emp_dept', '=', 'dbcpsupms.offices.id')
            ->select('employees.id', 'employees.fname', 'employees.lname', 'employees.mname', 'employees.profile', 'employees.bdate', 'dbcpsupms.offices.office_abbr')
            ->orderByRaw("
                CASE
                    WHEN DATE_FORMAT(employees.bdate, '%m-%d') >= ? THEN 0
                    ELSE 1
                END, DATE_FORMAT(employees.bdate, '%m-%d') ASC", [$today->format('m-d')])
            ->take(10)
            ->get()
            ->each(function ($employee) {
                $employee->bdate = Carbon::parse($employee->bdate);
            });
        
            return view("home.dashboard", compact('campCount', 'eliCount', 'workexpCount', 'learDevCount', 'volWorkCount', 'dtrCount', 'totalEmployees', 'leaveappCount', 'eliCount', 'offCount', 'userCount', 'chartEmployee', 'empStatusPercentages', 'upcomingBirthdays', 'guard'));
        }
    
        if (\Auth::guard('employee')->check()) {
            return view("home.dashboard", compact('campCount', 'offCount', 'userCount', 'chartEmployee', 'guard'));
        }
    }
    
    public function dashboard1(){
        $guard = $this->getGuard();
        $userCount = User::all();
        $campCount = Campus::all();
        $chartEmployee = Employee::all();
            
        $offCount = Office::all();
    
        if (\Auth::guard('web')->check()) {
            $empCount = (\Auth::guard('web')->user()->campus_id == 1)
                ? Employee::count()
                : Employee::where('emp_ID', \Auth::guard('web')->user()->campus_id)->count();

                return view("home.dashboard1", compact('campCount', 'empCount', 'offCount', 'userCount', 'chartEmployee', 'guard'));
        }
    
        if (\Auth::guard('employee')->check()) {
            return view("home.dashboard1", compact('campCount', 'offCount', 'userCount', 'chartEmployee', 'guard'));
        }
    }

    public function drive()
    {
        $guard = $this->getGuard();
        $docFolder = DocuFolder::all()->where('folder_category', 'mainfolder');
        $offices = Office::all();
        $office = null;
        if (\Auth::guard('employee')->check()) {
            $uid = auth()->guard('employee')->user()->id;
            $office = Office::where('office_head_id', $uid)->first();
        }
        return view("drive.drive", compact('docFolder', 'office', 'offices', 'guard'));
    }

    public function logout()
    {
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
            return redirect()->route('getLogin')->with('success', 'You have been successfully logged out');
        }

        if (Auth::guard('employee')->check()) {
            Auth::guard('employee')->logout();
            return redirect()->route('getLogin')
                             ->with('success', 'You have been successfully logged out');
        }

        return redirect()->route('getLogin')
                         ->with('error', 'No authenticated user to log out');
    }

}
