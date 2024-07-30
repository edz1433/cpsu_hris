<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;

class DriveAccountController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }
    
    public function driveAccount(){
        $guard = $this->getGuard();
        $employees = Employee::where('username', '!=', '')->get();
        return view("account.account", compact('guard', 'employees'));
    }
}
