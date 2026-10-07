<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Pmt;
use App\Models\Campus;
use App\Models\User;
use App\Models\DocuFolder;
use App\Models\Dtr;
use App\Models\LeaveApplication;
use App\Models\Eligibility;
use App\Models\WorkExperience;
use App\Models\LearningDev; 
use App\Models\VoluntaryWork;
use App\Models\Application;
use App\Models\JobHiring;
use App\Models\SpmsPersonnel;
use App\Models\Setting;
use App\Models\OfficialTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use App\Http\Controllers\Concerns\CalculatesTardiness;
use App\Services\DtrSheetPunches;

class MasterController extends Controller
{
    use CalculatesTardiness;

    private function formatDtrTime($value)
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('h:i A');
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * AM/PM columns exactly as the printed DTR shows them, formatted for display.
     */
    private function dtrSheetPunches($dtr)
    {
        $punches = DtrSheetPunches::forDay(optional($dtr)->time_in, optional($dtr)->time_out);

        return array_map(fn ($time) => $time ? $time->format('h:i A') : null, $punches);
    }

    private function arrangedDtrPunches($dtr)
    {
        if (!$dtr) {
            return collect();
        }

        return $this->dtrTimes($dtr->time_in)
            ->map(fn ($time) => ['time' => $time, 'label' => 'IN'])
            ->merge($this->dtrTimes($dtr->time_out)->map(fn ($time) => ['time' => $time, 'label' => 'OUT']))
            ->merge($this->dtrTimes($dtr->time_over)->map(fn ($time) => ['time' => $time, 'label' => 'OT']))
            ->sortBy(fn ($punch) => strtotime($punch['time']))
            ->values()
            ->map(function ($punch) {
                $punch['formatted'] = $this->formatDtrTime($punch['time']);

                return $punch;
            });
    }

    /**
     * "08:00 AM - 12:00 PM", or a dash when nothing is scheduled for that half.
     */
    private function scheduleHalfLabel($schedule, $startKey, $endKey)
    {
        if (!$schedule || empty($schedule[$startKey]) || empty($schedule[$endKey])) {
            return '—';
        }

        return $this->formatDtrTime($schedule[$startKey]) . ' - ' . $this->formatDtrTime($schedule[$endKey]);
    }

    private function firstDtrIn($dtr)
    {
        return $this->formatDtrTime($this->dtrTimes(optional($dtr)->time_in)->first());
    }

    private function lastDtrOut($dtr)
    {
        return $this->formatDtrTime($this->dtrTimes(optional($dtr)->time_out)->last());
    }

    /**
     * Payroll cutoff the given date falls in: the 1st-15th half of the month,
     * or the 16th through the last day (28th/29th/30th/31st).
     */
    private function semiMonthlyCutoff($date)
    {
        $date = Carbon::parse($date);

        if ($date->day <= 15) {
            return [
                'from' => $date->copy()->startOfMonth()->toDateString(),
                'to' => $date->copy()->day(15)->toDateString(),
            ];
        }

        return [
            'from' => $date->copy()->day(16)->toDateString(),
            'to' => $date->copy()->endOfMonth()->toDateString(),
        ];
    }

    private function formatMinutes($minutes)
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours <= 0) {
            return $remainingMinutes . ' min';
        }

        return $hours . ' hr' . ($hours == 1 ? '' : 's') . ' ' . $remainingMinutes . ' min';
    }

    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function dashboard(Request $request)
    {
        $guard = $this->getGuard();
        $userCount = User::all();
        $campCount = Campus::all();
        $dtrCount = Dtr::whereDate('date', Carbon::now('Asia/Manila')->toDateString())->count();
        $chartEmployee = Employee::where('stat_1', 1)->get();

        $leaveappCount = LeaveApplication::where('emp_esign', '=', 0)->where('history', 1)->where('status', 1)->count('empid');
        $eliCount = Eligibility::where('status', 0)->count();
        $workexpCount = WorkExperience::where('status', 0)->count();
        $learDevCount = LearningDev::where('status', 0)->count();
        $volWorkCount = VoluntaryWork::where('status', 0)->count();

        $totalEmployees = $chartEmployee->count();
        $empStatuses = [1, 2, 3, 4];

        // Calculate percentage for each emp_status and ensure the correct order
        $empStatusPercentages = collect($empStatuses)->mapWithKeys(function ($status) use ($chartEmployee, $totalEmployees) {
            $count = $chartEmployee->where('emp_status', $status)->count();
            $percentage = $totalEmployees > 0 ? ($count / $totalEmployees) * 100 : 0;
            return [$status => ['count' => $count, 'percentage' => $percentage]];
        });
        
        $offCount = Office::all();
    
        if (\Auth::guard('web')->check()) {
            $today = Carbon::now();
            $currentYear = $today->year;

            $today = Carbon::today();
            
            $upcomingBirthdays = Employee::whereNotNull('employees.bdate')
            ->join('dbcpsupms.offices', 'employees.emp_dept', '=', 'dbcpsupms.offices.id')
            ->select('employees.id', 'employees.fname', 'employees.lname', 'employees.mname', 'employees.profile', 'employees.bdate', 'dbcpsupms.offices.office_abbr')
            ->orderByRaw("
                CASE
                    WHEN DATE_FORMAT(employees.bdate, '%m-%d') >= ? THEN 0
                    ELSE 1
                END, DATE_FORMAT(employees.bdate, '%m-%d') ASC", [$today->format('m-d')])
            ->take(10)
            ->get()
            ->each(function ($employee) {
                $employee->bdate = Carbon::parse($employee->bdate);
            });
        
            return view("home.dashboard", compact('campCount', 'eliCount', 'workexpCount', 'learDevCount', 'volWorkCount', 'dtrCount', 'totalEmployees', 'leaveappCount', 'eliCount', 'offCount', 'userCount', 'chartEmployee', 'empStatusPercentages', 'upcomingBirthdays', 'guard'));
        }
    
        if (\Auth::guard('employee')->check()) {
            $employee = \Auth::guard('employee')->user();
            $officialTime = OfficialTime::where('empid', $employee->emp_ID)->first();
            $today = Carbon::now('Asia/Manila')->toDateString();
            $currentCutoff = $this->semiMonthlyCutoff(Carbon::now('Asia/Manila'));
            $dateFrom = $request->input('date_from', $currentCutoff['from']);
            $dateTo = $request->input('date_to', $currentCutoff['to']);

            if (Carbon::parse($dateFrom)->greaterThan(Carbon::parse($dateTo))) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }

            $todayDtr = Dtr::where('emp_ID', $employee->emp_ID)
                ->whereDate('date', $today)
                ->orderBy('time_in')
                ->first();

            $filteredDtrs = Dtr::where('emp_ID', $employee->emp_ID)
                ->whereBetween('date', [$dateFrom, $dateTo])
                ->orderBy('date', 'desc')
                ->orderBy('time_in', 'desc')
                ->get();

            $recentDtrs = $filteredDtrs;
            $isRegularEmployee = (int) $employee->emp_status === 1;

            $leaveApplications = $isRegularEmployee
                ? LeaveApplication::where('empid', $employee->emp_ID)
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get()
                : collect();

            $leaveCount = $isRegularEmployee
                ? LeaveApplication::where('empid', $employee->emp_ID)->count()
                : 0;
            $serviceYears = $employee->date_hired
                ? Carbon::parse($employee->date_hired)->diffInYears(Carbon::now('Asia/Manila'))
                : null;
            $todayTimeIn = $this->firstDtrIn($todayDtr);
            $todayTimeOut = $this->lastDtrOut($todayDtr);
            $todayPunches = $this->arrangedDtrPunches($todayDtr);
            $todayDailyPunches = $this->dtrSheetPunches($todayDtr);
            $tardinessSummary = $this->dtrTardinessTotals($filteredDtrs, $officialTime);
            $totalLate = $this->formatMinutes($tardinessSummary['late_minutes']);
            $totalUndertime = $this->formatMinutes($tardinessSummary['undertime_minutes']);
            $recentDtrs = $recentDtrs->map(function ($dtr) use ($officialTime) {
                $schedule = $this->officialScheduleForDate($officialTime, $dtr->date);

                $dtr->formatted_time_in = $this->firstDtrIn($dtr);
                $dtr->formatted_time_out = $this->lastDtrOut($dtr);
                $dtr->arranged_punches = $this->arrangedDtrPunches($dtr);
                // $schedule is null on days with no official working hours.
                $dtr->official_schedule = [
                    'am' => $this->scheduleHalfLabel($schedule, 'mornin', 'mornout'),
                    'pm' => $this->scheduleHalfLabel($schedule, 'aftin', 'aftout'),
                ];
                // Same AM/PM columns as the printed DTR, so the two never disagree.
                $dtr->daily_punches = $this->dtrSheetPunches($dtr);

                return $dtr;
            });

            return view("home.dashboard", compact(
                'campCount',
                'offCount',
                'userCount',
                'chartEmployee',
                'guard',
                'employee',
                'todayDtr',
                'todayTimeIn',
                'todayTimeOut',
                'todayPunches',
                'todayDailyPunches',
                'officialTime',
                'dateFrom',
                'dateTo',
                'filteredDtrs',
                'tardinessSummary',
                'totalLate',
                'totalUndertime',
                'recentDtrs',
                'leaveApplications',
                'leaveCount',
                'serviceYears',
                'isRegularEmployee'
            ));
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

        $docFolder = DocuFolder::where('folder_category', 'mainfolder')->get();
        $offices   = Office::all();

        $category = null;

        if ($guard === 'employee') {
            $userid = auth()->guard('employee')->user()->id;

            // ✅ returns int or null
            $category = SpmsPersonnel::where('empid', $userid)
                ->value('category');
        }

        $office = null;
        if (\Auth::guard('employee')->check()) {
            $uid = auth()->guard('employee')->user()->id;
            $office = Office::where('office_head_id', $uid)->first();
        }

        return view('drive.drive', compact(
            'docFolder',
            'category',
            'office',
            'offices',
            'guard'
        ));
    }

    public function logout()
    {
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
            return redirect()->route('getLogin')->with('success', 'You have been successfully logged out');
        }

        if (Auth::guard('employee')->check()) {
            Auth::guard('employee')->logout();
            return redirect()->route('getLogin')
                             ->with('success', 'You have been successfully logged out');
        }

        return redirect()->route('getLogin')
                         ->with('error', 'No authenticated user to log out');
    }

    public function dataPrivacy()
    {
        $guard = $this->getGuard();
        $customPaper = [0, 0, 684, 1050];
        $pdf = \PDF::loadView('data-privacy', compact('guard'))
            ->setPaper($customPaper, 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'margin-top' => 10,
                'margin-right' => 10,
                'margin-bottom' => 10,
                'margin-left' => 10,
            ])
            ->setCallbacks([
                'before_render' => function ($domPdf) {
                    $domPdf->getCanvas()->page_text(10, 10, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, [0, 0, 0]);
                },
            ]);

        return $pdf->stream(); // stream to iframe
    } 

    public function appList(Request $request){
        $guard = $this->getGuard();
        $request->validate([
            'position_id' => 'nullable|integer|exists:job_hirings,id',
            'status' => 'nullable|integer|in:0,1,2,3,4,5,6,7',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $query = Application::join('job_hirings', 'applications.jid', '=', 'job_hirings.id')
            ->select('applications.*', 'job_hirings.title as position', 'job_hirings.plantilla_item_no');

        if ($request->filled('position_id')) {
            $query->where('applications.jid', $request->position_id);
        }

        if ($request->filled('status')) {
            $query->where('applications.status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('applications.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('applications.created_at', '<=', $request->date_to);
        }

        $applications = $query
            ->orderByDesc('applications.created_at')
            ->get();
        $jobs = JobHiring::orderBy('title')->get();

        return view('career.application', compact('applications', 'jobs', 'guard'));
    }

    public function applicationReport(Request $request)
    {
        $statusLabels = [
            0 => 'Application Submitted',
            1 => 'Reviewing',
            2 => 'Qualified / Ready for Interview',
            3 => 'Disqualified',
            4 => 'Qualified yet not selected',
            5 => 'Top 5 / Psychological or Pre-Employment Test',
            6 => 'Not Hired',
            7 => 'Hired',
        ];

        $request->validate([
            'position_id' => 'nullable|integer|exists:job_hirings,id',
            'status' => 'nullable|integer|in:0,1,2,3,4,5,6,7',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $query = Application::join('job_hirings', 'applications.jid', '=', 'job_hirings.id')
            ->select(
                'applications.*',
                'job_hirings.title as position',
                'job_hirings.assignment as program',
                'job_hirings.education as required_education',
                'job_hirings.training as required_training',
                'job_hirings.experience as required_experience'
            );

        if ($request->filled('position_id')) {
            $query->where('applications.jid', $request->position_id);
        }

        if ($request->filled('status')) {
            $query->where('applications.status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('applications.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('applications.created_at', '<=', $request->date_to);
        }

        $applications = $query
            ->orderBy('job_hirings.title')
            ->orderBy('applications.last_name')
            ->orderBy('applications.first_name')
            ->get();

        $selectedPosition = $request->filled('position_id')
            ? JobHiring::find($request->position_id)
            : null;

        $selectedStatus = $request->filled('status')
            ? ($statusLabels[(int) $request->status] ?? 'Unknown')
            : 'All Statuses';
        $selectedDateFrom = $request->date_from;
        $selectedDateTo = $request->date_to;

        $customPaper = [0, 0, 1296, 612];
        $pdf = \PDF::loadView('career.application-report', compact(
            'applications',
            'selectedPosition',
            'selectedStatus',
            'selectedDateFrom',
            'selectedDateTo'
        ))->setPaper($customPaper);

        return $pdf->stream('application-report.pdf');
    }

    public function systemSetting()
    {
        $this->authorizeSystemSettings();

        $guard = $this->getGuard();
        $employees = Employee::select('id', 'emp_ID', 'fname', 'lname')
            ->orderBy('lname')
            ->orderBy('fname')
            ->get();
        $settings = Setting::firstOrCreate([], ['maintenance' => false]);

        $kioskAccess = array_filter(explode(',', (string) $settings->hr_kiosk));
        $dtrFullAccess = array_filter(explode(',', (string) $settings->dtr_acct));

        return view('settings.index', compact('guard', 'employees', 'settings', 'kioskAccess', 'dtrFullAccess'));
    }

    public function updateMaintenance(Request $request)
    {
        $this->authorizeSystemSettings();

        $validated = $request->validate([
            'maintenance' => ['required', 'boolean'],
        ]);

        $settings = Setting::firstOrCreate([], ['maintenance' => false]);
        $settings->maintenance = $validated['maintenance'];
        $settings->save();

        $message = $settings->maintenance
            ? 'Maintenance mode enabled. New logins are now blocked.'
            : 'Maintenance mode disabled. Users can log in again.';

        return redirect()->route('settings')->with('success', $message);
    }

    public function updateSettings(Request $request)
    {
        $this->authorizeSystemSettings();

        $validated = $request->validate([
            'suc_pres' => ['required', 'integer', 'exists:employees,id'],
            'vpaa' => ['nullable', 'integer', 'exists:employees,id'],
            'vpaf' => ['nullable', 'integer', 'exists:employees,id'],
            'hr' => ['required', 'integer', 'exists:employees,id'],
            'te_rstrct_lvl' => ['required', Rule::in([0, 1, 2])],
            'hr_kiosk' => ['nullable', 'array'],
            'hr_kiosk.*' => ['string', 'exists:employees,emp_ID'],
            'dtr_acct' => ['nullable', 'array'],
            'dtr_acct.*' => ['integer', 'exists:employees,id'],
            'records_office_email' => ['nullable', 'email', 'max:255'],
            'job_portal_email' => ['nullable', 'email', 'max:255'],
            'sync_backups' => ['required', 'boolean'],
        ], [], [
            'suc_pres' => 'SUC President',
            'vpaa' => 'Vice President of Academic Affairs',
            'vpaf' => 'Vice President of Administration and Finance',
            'hr' => 'HR Head',
            'te_rstrct_lvl' => 'Time Entry Restriction',
            'hr_kiosk' => 'HR Kiosk Access',
            'dtr_acct' => 'DTR Full Access',
            'sync_backups' => 'HR Kiosk Backtrack Sync',
        ]);

        $settings = Setting::firstOrCreate([], ['maintenance' => false]);
        $previousPres = (int) $settings->suc_pres;
        $previousHr = (int) $settings->hr;

        DB::transaction(function () use ($settings, $validated, $previousPres, $previousHr) {
            $settings->fill([
                'suc_pres' => $validated['suc_pres'],
                'vpaa' => $validated['vpaa'] ?? null,
                'vpaf' => $validated['vpaf'] ?? null,
                'hr' => $validated['hr'],
                'te_rstrct_lvl' => $validated['te_rstrct_lvl'],
                'hr_kiosk' => implode(',', array_unique($validated['hr_kiosk'] ?? [])),
                'dtr_acct' => implode(',', array_unique($validated['dtr_acct'] ?? [])),
                'records_office_email' => $validated['records_office_email'] ?? null,
                'job_portal_email' => $validated['job_portal_email'] ?? null,
                'sync_backups' => $validated['sync_backups'],
            ])->save();

            // Applications still waiting on a signature move to the newly assigned
            // signatory, so the new president/HR head can act on them and the
            // printed form carries their name. Signed ones keep the original signer.
            if ($previousPres !== (int) $settings->suc_pres) {
                LeaveApplication::where('history', 1)
                    ->where(fn ($q) => $q->whereNull('pres_sign')->orWhere('pres_sign', '!=', 2))
                    ->update([
                        'president' => $settings->suc_pres,
                        'pres_prefix' => Employee::whereKey($settings->suc_pres)->value('prefix'),
                    ]);
            }

            if ($previousHr !== (int) $settings->hr) {
                LeaveApplication::where('history', 1)
                    ->where('status', 1)
                    ->where(fn ($q) => $q->whereNull('hr_sign')->orWhere('hr_sign', '!=', 2))
                    ->update([
                        'hr' => $settings->hr,
                        'hr_prefix' => Employee::whereKey($settings->hr)->value('prefix'),
                    ]);
            }
        });

        // The kiosk API caches the restriction level for 30 seconds.
        Cache::forget('settings:te_rstrct');

        return redirect()->route('settings')->with('success', 'System settings saved.');
    }

    private function authorizeSystemSettings(): void
    {
        // Administrators only; see User::canAccessPage().
        abort_unless(
            Auth::guard('web')->check() && Auth::guard('web')->user()->canAccessPage('settings'),
            403
        );
    }

    public function dataPrivacyNotice(Request $request)
    {
        $guard = $this->getGuard();
        $user = Employee::find(auth()->guard($guard)->user()->id);
        $user->dpn = 1; 
        $user->save();

        return redirect()->back();
    }

}
