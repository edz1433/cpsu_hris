<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    // public function handleGoogleCallback()
    // {
    //     try {
    //         $google_user = Socialite::driver('google')->user();
    //         $email = $google_user->getEmail();

    //         $user = User::where('username', $email)->first();
    //         $employee = Employee::where('username', $email)->first();

    //         if (!$user && !$employee) {
    //             return redirect()->back()->with('error', 'We couldn\'t find your email. Please contact HR for assistance.');
    //         }

    //         $verification_code = mt_rand(100000, 999999);
    //         $recipient = $user ?? $employee;
    //         $recipient->verification_code = $verification_code;
    //         $recipient->save();

    //         $accounts = [
    //             [
    //                 'email' => 'hris-no-reply4@cpsu.edu.ph',
    //                 'password' => 'bqqc dtpt ypvr dfnh',
    //                 'name' => 'HRIS G-auth4',
    //             ],
    //             [
    //                 'email' => 'hris-no-reply3@cpsu.edu.ph',
    //                 'password' => 'erua ixxc mehl btkp',
    //                 'name' => 'HRIS G-auth3',
    //             ],
    //             [
    //                 'email' => 'hris-no-reply2@cpsu.edu.ph',
    //                 'password' => 'eweq azia fffi dsxs',
    //                 'name' => 'HRIS G-auth2',
    //             ],
    //             [
    //                 'email' => 'hris-no-reply1@cpsu.edu.ph',
    //                 'password' => 'idte slaa rnyy dzjh',
    //                 'name' => 'HRIS G-auth1',
    //             ],
    //             [
    //                 'email' => 'hris-no-reply7@cpsu.edu.ph',
    //                 'password' => 'zcjh pryj fvqu kqwo',
    //                 'name' => 'HRIS G-auth7',
    //             ],
    //             [
    //                 'email' => 'hris-no-reply8@cpsu.edu.ph',
    //                 'password' => 'ktcn tmua bozb wdvp',
    //                 'name' => 'HRIS G-auth8',
    //             ],
    //             [
    //                 'email' => 'hris-no-reply9@cpsu.edu.ph',
    //                 'password' => 'tmbj oiwy jkpg qjxl',
    //                 'name' => 'HRIS G-auth9',
    //             ],
    //             [
    //                 'email' => 'hris-no-reply10@cpsu.edu.ph',
    //                 'password' => 'yryg gghl lflq baga',
    //                 'name' => 'HRIS G-auth10',
    //             ],
    //         ];

    //         $mailSent = false;

    //         foreach ($accounts as $account) {
    //             try {
    //                 // Override config for this mail attempt
    //                 Config::set('mail.mailers.smtp', [
    //                     'transport' => 'smtp',
    //                     'host' => 'smtp.gmail.com',
    //                     'port' => 587,
    //                     'encryption' => 'tls',
    //                     'username' => $account['email'],
    //                     'password' => $account['password'],
    //                 ]);

    //                 Config::set('mail.from.address', $account['email']);
    //                 Config::set('mail.from.name', $account['name']);

    //                 Mail::mailer('smtp')->raw("Your verification code is: $verification_code", function ($message) use ($recipient) {
    //                     $message->to($recipient->username)
    //                             ->subject('Verification Code');
    //                 });

    //                 $mailSent = true;
    //                 break; // success, break loop

    //             } catch (\Exception $e) {
    //                 Log::warning("Email send failed using {$account['email']}: " . $e->getMessage());
    //                 continue; // try next fallback
    //             }
    //         }

    //         if (!$mailSent) {
    //             return redirect()->back()->with('error', 'All mail accounts failed. Please try again later.');
    //         }

    //         session()->flash('email', $recipient->username);
    //         return redirect()->route('verify');

    //     } catch (\Exception $e) {
    //         Log::error('Google OAuth error: ' . $e->getMessage());
    //         return redirect()->back()->with('error', 'There was an issue with Google OAuth. Please try again.');
    //     }
    // }


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
                return redirect("http://hris.cpsu.edu.ph/pms/hr-payroll-login/{$user->username}/h7hhpzg9GAIFGQ22ksORhu3NfgaTCLhgEI5j8COA");
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
