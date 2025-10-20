<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ApplicationController extends Controller
{
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'age' => 'required|integer|min:18|max:65',
            'sex' => 'required|in:male,female,other',
            'mobile' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'address' => 'required|string',
            'education' => 'required|array',
            'education.*' => 'required|string',
            'elevel' => 'required|array',
            'elevel.*' => 'required|string',
            'eyear' => 'required|array',
            'eyear.*' => 'required|string',
            'eligibility' => 'required|array',
            'eligibility.*' => 'required|string',
            'pds' => 'required|file|mimes:pdf|max:20480',
            'wes' => 'required|file|mimes:pdf|max:20480',
            'ilf' => 'required|file|mimes:pdf|max:20480',
            'resume' => 'required|file|mimes:pdf|max:20480',
            'tor' => 'required|file|mimes:pdf|max:20480',
            'coe' => 'nullable|file|mimes:pdf|max:20480',
            'cot' => 'nullable|file|mimes:pdf|max:20480'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create uploads directory if it doesn't exist
            $uploadPath = 'public/Uploads/applicant-files';
            if (!Storage::exists($uploadPath)) {
                Storage::makeDirectory($uploadPath);
            }

            // Process file uploads
            $pdsFile = $this->uploadFile($request->file('pds'), $uploadPath);
            $wesFile = $this->uploadFile($request->file('wes'), $uploadPath);
            $ilfFile = $this->uploadFile($request->file('ilf'), $uploadPath);
            $resumeFile = $this->uploadFile($request->file('resume'), $uploadPath);
            $torFile = $this->uploadFile($request->file('tor'), $uploadPath);
            $coeFile = $request->file('coe') ? $this->uploadFile($request->file('coe'), $uploadPath) : null;
            $cotFile = $request->file('cot') ? $this->uploadFile($request->file('cot'), $uploadPath) : null;

            // Create application record
            $application = Application::create([
                'job_id' => $request->job_id ?? 1, // Default to 1 if not provided
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'age' => $request->age,
                'sex' => $request->sex,
                'mobile' => $request->mobile,
                'email' => $request->email,
                'address' => $request->address,
                'education' => json_encode($request->education),
                'elevel' => json_encode($request->elevel),
                'eyear' => json_encode($request->eyear),
                'eligibility' => json_encode($request->eligibility),
                'pds' => $pdsFile,
                'wes' => $wesFile,
                'ilf' => $ilfFile,
                'resume' => $resumeFile,
                'tor' => $torFile,
                'coe' => $coeFile,
                'cot' => $cotFile,
                'application_date' => Carbon::now(),
                'status' => 'pending'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully!',
                'application_id' => $application->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error submitting application: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle file upload with unique filename
     */
    private function uploadFile($file, $path)
    {
        if (!$file) return null;
        
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $filename = $originalName . '_' . time() . '.' . $extension;
        
        $file->storeAs($path, $filename);
        
        return $filename;
    }

    public function setCtrlNo(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:applications,id',
            'ctrl_no' => 'required|string|max:50',
        ]);

        // ======================================================
        // Fetch application data along with job title (position)
        // ======================================================
        $app = Application::join('job_hirings', 'applications.jid', '=', 'job_hirings.id')
            ->select('applications.*', 'job_hirings.title as position')
            ->where('applications.id', $request->id)
            ->firstOrFail();

        // Update application
        $app->ctrl_no = $request->ctrl_no;
        $app->status = 1; // "Reviewing"
        $app->save();

        // ===============================
        // Send Email Notification
        // ===============================
        $firstName = ucwords(strtolower($app->first_name ?? ''));
        $lastName  = ucwords(strtolower($app->last_name ?? ''));
        $email     = $app->email ?? null;

        if ($email) {
            // Determine salutation
            $salute = 'Dear';
            if (!empty($app->sex)) {
                $sex = strtolower($app->sex);
                $salute = $sex === 'male' || $sex === 'm' ? 'Dear Mr.' :
                        ($sex === 'female' || $sex === 'f' ? 'Dear Ms.' : 'Dear');
            }

            $subject = 'CPSU Application Update – Under Review';
            $color = '#0ea5e9'; // Blue for "Reviewing"
            $position = e($app->position ?? 'the advertised position');

            $message = "
                <p>{$salute} <strong>{$lastName}</strong>,</p>
                <p>We have received your application for the position of <strong>{$position}</strong> at <strong>Central Philippines State University (CPSU)</strong>.</p>
                <p>Your application is now <strong>under review</strong> by our HR Department.</p>
                <p>We will contact you once there is an update regarding the next steps of the hiring process.</p>
                <p>Thank you for your interest in joining CPSU.</p>
            ";

            $body = '
            <div style="font-family:Arial, sans-serif;background:#f9fafb;padding:20px;">
                <div style="max-width:600px;margin:auto;background:#fff;border-radius:8px;overflow:hidden;
                    box-shadow:0 0 10px rgba(0,0,0,0.05);">
                    <div style="background:' . $color . ';color:#fff;padding:16px 24px;text-align:center;">
                        <h2 style="margin:0;font-size:20px;">' . e($subject) . '</h2>
                    </div>
                    <div style="padding:20px;color:#333;">
                        ' . $message . '
                        <p style="margin-top:20px;">Best regards,<br><strong>CPSU HR Department</strong></p>
                    </div>
                    <div style="background:#f1f1f1;text-align:center;padding:10px;font-size:12px;color:#555;">
                        © ' . date('Y') . ' Central Philippines State University | HR Department
                    </div>
                </div>
            </div>';

            Mail::send([], [], function ($m) use ($email, $subject, $body) {
                $m->to($email)
                ->from(config('mail.from.address'), config('mail.from.name'))
                ->subject($subject)
                ->html($body);
            });
        }

        return redirect()->back()->with('success', 'Control number set successfully! Email notification sent to applicant.');
    }

    public function updateStatus(Request $request)
    {
        // ------------------------------
        // 🔍 Validate Incoming Request
        // ------------------------------
        $request->validate([
            'id' => 'required|integer|exists:applications,id',
            'status' => 'required|integer|in:1,2,3,4,5,6,7',
            'reason' => 'nullable|string|max:500',
            'interview_datetime' => 'nullable|date',
            'venue' => 'nullable|string|max:255',
        ]);

        // ------------------------------
        // 📄 Retrieve Application Record
        // ------------------------------
        $app = Application::join('job_hirings', 'applications.jid', '=', 'job_hirings.id')
            ->select('applications.*', 'job_hirings.title as position')
            ->where('applications.id', $request->id)
            ->firstOrFail();

        $oldStatus = $app->status;
        $app->status = $request->status;

        // ------------------------------
        // ✅ Mark Completed Statuses
        // ------------------------------
        $app->is_complete = in_array($request->status, [3, 4, 6, 7]) ? 1 : 0;

        // ------------------------------
        // ⚙️ Handle Status-Specific Fields
        // ------------------------------
        if ($request->status == 2) { // Qualified for Interview
            $app->interview_datetime = $request->interview_datetime;
            $app->venue = $request->venue;
            $app->dq_reason = null;
        } elseif ($request->status == 3) { // Disqualified
            $app->dq_reason = $request->reason;
            $app->interview_datetime = null;
            $app->venue = null;
        } else {
            $app->dq_reason = null;
            $app->interview_datetime = null;
            $app->venue = null;
        }

        $app->save();

        if ($request->status == 7) {
            Application::where('jid', $app->jid)
                ->where('id', '!=', $app->id)
                ->whereNotIn('status', [3, 4, 6, 7]) // Skip disqualified, not selected, not hired, already hired
                ->update([
                    'status' => 6,        // Not Hired
                    'is_complete' => 1,   // Mark as completed
                    'updated_at' => now()
                ]);
        }

        // ------------------------------
        // 👤 Prepare Applicant Details
        // ------------------------------
        $firstName = ucwords(strtolower($app->first_name ?? ''));
        $lastName  = ucwords(strtolower($app->last_name ?? ''));
        $fullName  = trim("{$firstName} {$lastName}");
        $email     = $app->email ?? null;

        if (!$email) {
            return back()->with('error', 'Applicant has no valid email address.');
        }

        // ------------------------------
        // 🪶 Determine Salutation
        // ------------------------------
        $salute = match (strtolower($app->sex ?? '')) {
            'male', 'm' => 'Dear Mr.',
            'female', 'f' => 'Dear Ms.',
            default => 'Dear',
        };

        // ------------------------------
        // 🎨 Define Status Colors
        // ------------------------------
        $colors = [
            1 => '#0ea5e9', // Under Review
            2 => '#059669', // Qualified for Interview
            3 => '#dc2626', // Disqualified
            4 => '#f59e0b', // Qualified but Not Selected
            5 => '#2563eb', // For Test
            6 => '#6b7280', // Not Hired
            7 => '#16a34a', // Hired
        ];

        $color = $colors[$app->status] ?? '#374151';
        $subject = '';
        $message = '';

        // ------------------------------
        // ✉️ Email Content by Status
        // ------------------------------
        switch ($app->status) {
            case 2: // Qualified for Interview
                $formattedDate = $app->interview_datetime
                    ? Carbon::parse($app->interview_datetime)->format('F j, Y \a\t g:i A')
                    : 'To be announced';
                $venue = e($app->venue ?? 'To be announced');

                $subject = 'CPSU Interview Invitation';
                $message = "
                    <p>{$salute} <strong>{$lastName}</strong>,</p>
                    <p>Good day.</p>
                    <p>We are pleased to inform you that you have been shortlisted for the position of 
                    <strong>" . e($app->position) . "</strong> at <strong>Central Philippines State University (CPSU)</strong>.</p>
                    <p>Your interview is scheduled on <strong>{$formattedDate}</strong> at <strong>{$venue}</strong>.</p>
                    <p>Please acknowledge receipt of this message and confirm your availability for the interview.</p>
                    <p>Thank you for your continued interest in joining CPSU.</p>
                ";
                break;

            case 3: // Disqualified
                $reason = nl2br(e($app->dq_reason ?? 'Not specified.'));
                $subject = 'CPSU Application Update – Disqualified';
                $message = "
                    <p>{$salute} <strong>{$lastName}</strong>,</p>
                    <p>Thank you for your interest in employment at <strong>Central Philippines State University (CPSU)</strong>.</p>
                    <p>After careful review, we regret to inform you that your application was not able to proceed due to the following reason:</p>
                    <div style='background:#fee2e2;border-left:4px solid {$color};padding:10px 16px;margin:10px 0;color:#b91c1c;'>
                        {$reason}
                    </div>
                    <p>We sincerely appreciate your effort and interest, and we encourage you to apply again in the future.</p>
                ";
                break;

            case 4: // Qualified but Not Selected
                $subject = 'CPSU Application Result – Not Selected';
                $message = "
                    <p>{$salute} <strong>{$lastName}</strong>,</p>
                    <p>We wish to extend our gratitude for your participation in the interview process for the 
                    position of <strong>" . e($app->position) . "</strong> at <strong>Central Philippines State University (CPSU)</strong>.</p>
                    <p>Following a thorough evaluation, another applicant was selected whose qualifications more closely meet the current needs of the University.</p>
                    <p>We appreciate your time and encourage you to apply for future opportunities with CPSU.</p>
                ";
                break;

            case 5: // For Psychological / Pre-Employment Test
                $subject = 'CPSU Application Update – Next Stage';
                $message = "
                    <p>{$salute} <strong>{$lastName}</strong>,</p>
                    <p>We are pleased to inform you that you have been endorsed to advance to the next stage of the recruitment process 
                    for the position of <strong>" . e($app->position) . "</strong> at <strong>Central Philippines State University (CPSU)</strong>.</p>
                    <p>This stage will consist of a <strong>Psychological / Pre-Employment Test</strong>. Details regarding your schedule and venue 
                    will be sent to you shortly.</p>
                    <p>Please check your email regularly for further instructions from the CPSU Human Resources Department.</p>
                ";
                break;

            case 6: // Not Hired
                $subject = 'CPSU Application Result – Not Hired';
                $message = "
                    <p>{$salute} <strong>{$lastName}</strong>,</p>
                    <p>We sincerely appreciate the time and effort you devoted to the selection process for the 
                    position of <strong>" . e($app->position) . "</strong> at <strong>Central Philippines State University (CPSU)</strong>.</p>
                    <p>After careful consideration, we regret to inform you that you have not been selected for this position.</p>
                    <p>We wish you continued success in your professional journey.</p>
                ";
                break;

            case 7: // Hired
                $subject = 'CPSU Application Update – Congratulations!';
                $message = "
                    <p>{$salute} <strong>{$lastName}</strong>,</p>
                    <p>Congratulations!</p>
                    <p>We are delighted to inform you that you have been selected for the position of 
                    <strong>" . e($app->position) . "</strong> at <strong>Central Philippines State University (CPSU)</strong>.</p>
                    <p>The CPSU Human Resources Department will contact you soon regarding onboarding procedures and employment documentation.</p>
                    <p>Welcome to the CPSU community!</p>
                ";
                break;

            default:
                $subject = 'CPSU Application Status Update';
                $message = "
                    <p>{$salute} <strong>{$lastName}</strong>,</p>
                    <p>Your application status has been updated. Please log in to your applicant portal for further details.</p>
                ";
        }

        // ------------------------------
        // 💌 Email Wrapper Template
        // ------------------------------
        $body = '
            <div style="font-family:Arial, sans-serif;background:#f9fafb;padding:20px;">
                <div style="max-width:600px;margin:auto;background:#fff;border-radius:8px;overflow:hidden;
                    box-shadow:0 0 10px rgba(0,0,0,0.05);">
                    <div style="background:' . $color . ';color:#fff;padding:16px 24px;text-align:center;">
                        <h2 style="margin:0;font-size:20px;">' . e($subject) . '</h2>
                    </div>
                    <div style="padding:20px;color:#333;">
                        ' . $message . '
                        <p style="margin-top:20px;">Best regards,<br><strong>CPSU Human Resources Department</strong></p>
                    </div>
                    <div style="background:#f1f1f1;text-align:center;padding:10px;font-size:12px;color:#555;">
                        © ' . date('Y') . ' Central Philippines State University | Human Resources Department
                    </div>
                </div>
            </div>';

        // ------------------------------
        // 📧 Send Email Notification
        // ------------------------------
        Mail::send([], [], function ($m) use ($email, $subject, $body) {
            $m->to($email)
                ->from('career@cpsu.edu.ph', 'CPSU Human Resources Department')
                ->subject($subject)
                ->html($body);
        });

        // ------------------------------
        // 🔁 Redirect with Confirmation
        // ------------------------------
        return back()->with('success', "Applicant status successfully updated. An email notification has been sent to {$email}.");
    }


}