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

            $verification_code = (string) random_int(100000, 999999);
            $green = '#187744';

            // 🧩 Determine recipient (User or Employee)
            $recipient = $user ?? $employee;
            $recipient->verification_code = $verification_code;
            $recipient->save();

            // 🧍 Get first and last name from Google
            $firstName = $google_user->user['given_name'] ?? strtok($google_user->getName(), ' ');
            $lastName = $google_user->user['family_name'] ?? '';

            // 💌 Styled HTML email
            $body = '
            <div style="font-family: Arial, sans-serif; background-color: #f9fafb; padding:20px;">
                <div style="max-width:600px;margin:auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 0 10px rgba(0,0,0,0.05);">
                    <div style="background-color:'.$green.';color:#fff;padding:16px 24px;text-align:center;">
                    <h2 style="margin:0;font-size:20px;">HRIS Account Verification</h2>
                    </div>
                    <div style="padding:20px;color:#333;">
                    <p>Dear <strong>' . e(ucwords(strtolower($firstName . ' ' . $lastName))) . '</strong>,</p>
                    <p>We received a login request for your HRIS account using your personal Google account.</p>
                    <p>Please use the verification code below to complete your sign-in process:</p>
                    <div style="background:#f0fdf4;border-left:4px solid '.$green.';padding:12px 16px;margin:20px 0;text-align:center;font-size:24px;font-weight:bold;color:'.$green.';letter-spacing:4px;">
                        ' . $verification_code . '
                    </div>
                    <p>If you did not initiate this login, please ignore this email for your security.</p>
                    <p style="margin-top:20px;">Best regards,<br><strong>CPSU HRIS Team</strong></p>
                    </div>
                    <div style="background:#f1f1f1;text-align:center;padding:10px;font-size:12px;color:#555;">
                    © ' . date('Y') . ' Central Philippines State University | HRIS
                    </div>
                </div>
            </div>';

            // ✉️ Send styled email (FROM values from .env)
            Mail::send([], [], function ($message) use ($recipient, $body) {
                $message->to($recipient->username)
                        ->from(config('mail.from.address'), config('mail.from.name'))
                        ->subject('CPSU Login Verification Code')
                        ->html($body);
            });

            // 🧠 Store session for verification page
            session()->flash('email', $recipient->username);
            // The code is only accepted for the account Google confirmed, in this browser, for 10 minutes.
            session()->put('google_verify', [
                'email' => $recipient->username,
                'expires_at' => now()->addMinutes(10)->getTimestamp(),
            ]);

            return redirect()->route('verify');

        } catch (\Exception $e) {
            \Log::error('❌ Google OAuth Error: ' . $e->getMessage());
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
        $verification_code = (string) $request->input('verification_code');
        // Trust only the email Google confirmed for this session, never the posted one.
        $pending = $request->session()->get('google_verify');
        $email = is_array($pending) && ($pending['expires_at'] ?? 0) >= now()->getTimestamp()
            ? $pending['email'] : null;

        if (!$email || !preg_match('/^\d{6}$/', $verification_code)) {
            return redirect()->route('getLogin')->with('error', 'Invalid verification code, or it has expired. Please sign in again.');
        }

        $user = User::where('username', $email)->first();
        $employee = Employee::where('username', $email)->first();
        // A used code is cleared to null, so an empty stored code must never match.
        $matches = fn ($account) => $account && (string) $account->verification_code !== ''
            && hash_equals((string) $account->verification_code, $verification_code);

        if ($matches($user) || $matches($employee)) {
            $request->session()->forget('google_verify');
        }

        if ($matches($user)) {
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
        
        if ($matches($employee)) {
            $employee->verification_code = null;
            $employee->save();
    
            if ($employee->stat_1 == 1) {
                Auth::guard('employee')->login($employee);
                return redirect()->route('dashboard')->with('success', 'Login Successfully');
            } else {
                return redirect()->back()->with('error', 'Account Suspended');
            }
        }
    
        return redirect()->back()->with('error', 'Invalid Verification Code');
    }
    
}
