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
    //     // try {
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

    //     // } catch (\Exception $e) {
    //     //     Log::error('Google OAuth error: ' . $e->getMessage());
    //     //     return redirect()->back()->with('error', 'There was an issue with Google OAuth. Please try again.');
    //     // }
    // }

    public function handleGoogleCallback()
    {
        try {
            $google_user = Socialite::driver('google')->user();
            $email = strtolower(trim($google_user->getEmail()));

            $user = User::whereRaw('LOWER(username) = ?', [$email])->first();
            $employee = Employee::whereRaw('LOWER(username) = ?', [$email])
                ->orWhereRaw('LOWER(org_email) = ?', [$email])
                ->first();
            
            if (!$user && !$employee) {
                return redirect()->back()->with('error', 'We couldn\'t find your email. Please contact HR for assistance.');
            }

            $verification_code = mt_rand(100000, 999999);
            $green = '#187744';

            // 🧩 Determine recipient (User or Employee)
            $recipient = $user ?? $employee;
            $recipientEmail = $user ? $user->username : ($employee->org_email ?? $employee->username);
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
            Mail::send([], [], function ($message) use ($recipientEmail, $body) {
                $message->to($recipientEmail)
                        ->from(config('mail.from.address'), config('mail.from.name'))
                        ->subject('CPSU Login Verification Code')
                        ->html($body);
            });

            // 🧠 Store session for verification page
            session()->flash('email', $recipient->username);

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
