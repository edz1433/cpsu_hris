<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\LeaveCredit;

class LeaveCredits extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }  

    public function leavesRead($id = null){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);
        $leaves = LeaveCredit::where('empid', $employee->emp_ID)->first();

        return view('leaves.emp-leaves', compact('leaves', 'guard', 'employee'));
    }

    public function leavesCreate(){

    }
}
