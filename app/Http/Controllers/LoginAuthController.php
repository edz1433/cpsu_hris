<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;

class LoginAuthController extends Controller
{

    public function getLoginAdmin()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }elseif(Auth::guard('employee')->check()){
            return redirect()->route('drive');
        }
        
        return view('login-page');
    }

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
        if (auth()->guard('web')->attempt([
            'username' => $request->username,
            'password' => $request->password,
        ])) {
            // If authenticated as admin
            $admin = auth()->guard('web')->user();
            $role = $admin->role;
    
            if ($role == "Payroll Administrator") {
                auth()->guard('web')->logout();
                return redirect("https://hris.cpsu.edu.ph/pms/hr-payroll-login/{$request->username}/{$request->password}");
            }
    
            return redirect()->route('dashboard')->with('success', 'Login Successfully');
        }
        
        $employee = Employee::where('username', $request->username)->first();
    
        if ($employee) {
            if ($employee->stat_1 == 1) {
                if (auth()->guard('employee')->attempt([
                    'username' => $request->username,
                    'password' => $request->password,
                ])) {
                    return redirect()->route('empPDS')->with('success', 'Login Successfully');
                } else {
                    return redirect()->back()->with('error', 'Invalid Credentials');
                }
            } else {
                return redirect()->back()->with('error', 'Account Suspended');
            }
        }
    
        return redirect()->back()->with('error', 'Invalid Credentials');
    }
    
}
