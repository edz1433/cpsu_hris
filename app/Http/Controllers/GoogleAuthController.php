<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $google_user = Socialite::driver('google')->user();
            $email = $google_user->getEmail();
        
            $user = User::where('username', $email)->first();
            $employee = Employee::where('username', $email)->first();
            
            if (!$user && !$employee) {
                return redirect()->back()->with('error', 'We couldn\'t find your email. Please contact HR for assistance.');
            }
    
            $verification_code = mt_rand(100000, 999999);
    
            if ($user) {
                $user->verification_code = $verification_code;
                $user->save();
                
                Mail::raw("Your verification code is: $verification_code", function ($message) use ($user) {
                    $message->to($user->username)
                            ->subject('Verification Code');
                });
    
                session()->flash('email', $user->username);
            } elseif ($employee) {
                $employee->verification_code = $verification_code;
                $employee->save();
                
                Mail::raw("Your verification code is: $verification_code", function ($message) use ($employee) {
                    $message->to($employee->username)
                            ->subject('Verification Code');
                });
    
                session()->flash('email', $employee->username);
            }
    
            return redirect()->route('verify');
    
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'There was an issue with Google OAuth. Please try again.');
        }
    }
       
    
    public function verifyForm(Request $request)
    {
        $email = $request->session()->get('email');
        return view('verify', compact('email'));
    }

    public function verify(Request $request)
    {
        $verification_code = $request->input('verification_code');
        $email = $request->input('email'); 

        $user = User::where('username', $email)->first();
        $employee = Employee::where('username', $email)->first();
    
        if ($user && $user->verification_code == $verification_code) {
            $user->verification_code = null;
            $user->save();
    
            $role = $user->role;
            if ($role == "Payroll Administrator") {
                Auth::logout();
                return redirect("http://hris.cpsu.edu.ph:20000/hr-payroll-login/{$user->username}/h7hhpzg9GAIFGQ22ksORhu3NfgaTCLhgEI5j8COA");
            }
            
            Auth::login($user);
            return redirect()->route('dashboard')->with('success', 'Login Successfully');
        } 
        
        if ($employee && $employee->verification_code == $verification_code) {
            $employee->verification_code = null;
            $employee->save();
    
            if ($employee->stat_1 == 1) {
                Auth::guard('employee')->login($employee);
                return redirect()->route('empPDS')->with('success', 'Login Successfully');
            } else {
                return redirect()->back()->with('error', 'Account Suspended');
            }
        }
    
        return redirect()->back()->with('error', 'Invalid Verification Code');
    }
    
}
