<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Campus;
use App\Models\User;
use App\Models\DocuFolder;

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
            
        $offCount = Office::all();
    
        if (\Auth::guard('web')->check()) {
            $empCount = (\Auth::guard('web')->user()->campus_id == 1)
                ? Employee::count()
                : Employee::where('emp_ID', \Auth::guard('web')->user()->campus_id)->count();

                return view("home.dashboard", compact('campCount', 'empCount', 'offCount', 'userCount', 'chartEmployee', 'guard'));
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
        if (\Auth::guard('web')->check() || \Auth::guard('employee')->check()) {
            auth()->guard('web')->logout();
            auth()->guard('employee')->logout();
            return redirect()->route('getLogin')->with('success', 'You have been Successfully Logged Out');
        } else {
            return redirect()->route('drive')->with('error', 'No authenticated user to log out');
        }
    }
    
}
