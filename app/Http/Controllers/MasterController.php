<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Campus;
use App\Models\User;
use App\Models\DocuFolder;
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
        $chartEmployee = Employee::all();

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

            $empCount = (\Auth::guard('web')->user()->campus_id == 1)
                ? Employee::count()
                : Employee::where('emp_ID', \Auth::guard('web')->user()->campus_id)->count();

            // Get today's date formatted for comparison
            $today = Carbon::today();

            // Fetch upcoming birthdays
            $upcomingBirthdays = Employee::whereNotNull('bdate') // Ensure 'bdate' is not null
            ->select('id', 'fname', 'lname', 'mname', 'profile', 'bdate') // Select necessary fields
            ->orderByRaw("CASE 
                WHEN DATE_FORMAT(bdate, '%m-%d') >= ? THEN DATEDIFF(CONCAT(YEAR(?), '-', DATE_FORMAT(bdate, '%m-%d')), ?)
                ELSE DATEDIFF(CONCAT(YEAR(?) + 1, '-', DATE_FORMAT(bdate, '%m-%d')), ?) 
            END", [$today->format('m-d'), $today->format('Y-m-d'), $today->format('Y-m-d'), $today->format('Y'), $today->format('Y-m-d')]) // Calculate days until the birthday
            ->take(7) // Limit to 7 records
            ->get()
            ->each(function ($employee) {
                // Convert 'bdate' to a Carbon instance
                $employee->bdate = Carbon::parse($employee->bdate);
            });

            // // Debug output to check upcoming birthdays
            // dd($upcomingBirthdays);

            return view("home.dashboard", compact('campCount', 'empCount', 'offCount', 'userCount', 'chartEmployee', 'empStatusPercentages', 'upcomingBirthdays', 'guard'));
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
