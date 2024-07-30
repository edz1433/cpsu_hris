<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;

class LoginAuthController extends Controller
{
    public function getLogin()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }elseif(Auth::guard('employee')->check()){
            return redirect()->route('drive');
        }

        return view('login');
    }
    
    protected function postLogin(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);
    
        // Attempt to authenticate admin
        $validatedAdmin = auth()->guard('web')->attempt([
            'username' => $request->username,
            'password' => $request->password,
        ]);
    
        // Attempt to authenticate employee
        $employee = Employee::where('username', $request->username)->first();
    
        if ($employee && $employee->stat_1 == 1) {
            $validateEmp = auth()->guard('employee')->attempt([
                'username' => $request->username,
                'password' => $request->password,
            ]);
    
            if ($validateEmp) {
                return redirect()->route('empPDS')->with('success', 'Login Successfully');
            } else {
                return redirect()->back()->with('error', 'Invalid Credentials');
            }
        } elseif ($employee && $employee->stat_1 != 1) {
            return redirect()->back()->with('error', 'Account Suspended');
        } elseif ($validatedAdmin) {
            return redirect()->route('dashboard')->with('success', 'Login Successfully');
        } else {
            return redirect()->back()->with('error', 'Invalid Credentials');
        }
    }
    
}
