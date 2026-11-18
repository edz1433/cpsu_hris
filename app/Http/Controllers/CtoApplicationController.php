<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\Notification;
use App\Models\PayrollEmployee;
use App\Models\Setting;
use App\Services\LeaveNotice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Compensatory Time-Off (CTO). Stored with leave applications (leave_type 16)
 * so it shows on Status, History, Pending and the leave card, but it has its
 * own, shorter approval chain and never goes through the leave steps:
 *
 *   1. Employee files it, already e-signed            status 1
 *   2. Immediate supervisor (or OIC) approves/declines status 2 when approved
 *   3. HR approves/declines, final; hours deducted     status 4, history 2
 *
 * A decline at either step closes it (remarks_stat 2 = supervisor, 1 = HR).
 */
class CtoApplicationController extends Controller
{
    public const WAITING_SUPERVISOR = 1;
    public const WAITING_HR = 2;
    public const APPROVED = 4;

    public function create(Request $request)
    {
        // Rows come back to the CTO tab, not the leave form, on any error.
        $back = fn ($message) => redirect()->back()->withInput()->with('error', $message)->with('leave_tab', 'cto');

        $employee = auth()->guard('employee')->user();
        if (!$employee) {
            return $back('Only employees can file a CTO application.');
        }
        $employee = Employee::find($employee->id);

        $validator = \Validator::make($request->all(), [
            'cto' => 'required|array|min:1|max:31',
            'cto.*.date' => 'required|date_format:Y-m-d|distinct',
            'cto.*.hours' => 'required|numeric|min:0.5|max:10',
        ], [
            'cto.required' => 'Add at least one date for your CTO.',
            'cto.*.date.required' => 'Pick a date on every row.',
            'cto.*.date.date_format' => 'Pick the dates from the calendar.',
            'cto.*.date.distinct' => 'Each date can only be listed once.',
            'cto.*.hours.required' => 'Enter the number of hours on every row.',
            'cto.*.hours.min' => 'Hours per date must be at least 0.5.',
            'cto.*.hours.max' => 'Hours per date cannot be more than 10.',
        ]);

        if ($validator->fails()) {
            return $back($validator->errors()->first());
        }

        // The CTO is signed when it is filed, so the e-signature must exist.
        if (empty($employee->esign)) {
            return $back('Upload your e-signature first (PDS > Signature). It is attached to your CTO when you submit.');
        }

        if (LeaveApplication::where('empid', $employee->emp_ID)->where('history', 1)->whereNotIn('status', [3, 4])->exists()) {
            return $back('One Leave Application at a time');
        }

        if (is_null($employee->supervisor) || $employee->supervisor == 0) {
            return $back('No Supervisor Assigned');
        }

        $rows = collect($request->input('cto'))
            ->map(fn ($row) => ['date' => $row['date'], 'hours' => round((float) $row['hours'], 2)])
            ->sortBy('date')
            ->values();
        $totalHours = round($rows->sum('hours'), 2);
        $balance = round((float) ($employee->coc_leave ?? 0), 2);

        if ($totalHours > $balance) {
            return $back('Insufficient Compensatory Overtime Credits: you have ' . LeaveApplication::formatHours($balance)
                . ' hrs, but asked for ' . LeaveApplication::formatHours($totalHours) . ' hrs.');
        }

        $setting = Setting::first();
        $payrollEmployee = PayrollEmployee::forEmpId($employee->emp_ID)->first();
        $first = $rows->first()['date'];
        $last = $rows->last()['date'];

        DB::transaction(function () use ($employee, $setting, $payrollEmployee, $rows, $totalHours, $balance, $first, $last) {
            $lastTransnum = LeaveApplication::orderBy('id', 'desc')->lockForUpdate()->first();
            $transnum = str_pad($lastTransnum ? intval($lastTransnum->transnum) + 1 : 1, 6, '0', STR_PAD_LEFT);

            $cto = LeaveApplication::create([
                'transnum' => $transnum,
                'empid' => $employee->emp_ID,
                'position' => $employee->position,
                'leave_type' => LeaveApplication::TYPE_CTO,
                'date_range' => $first === $last ? $first : "{$first} to {$last}",
                'days' => $rows->count(),
                'cto_hours' => $totalHours,
                'coc_balance' => $balance,
                'total_vl' => $employee->vl,
                'total_sl' => $employee->sl,
                'date_filing' => Carbon::now('Asia/Manila')->format('Y-m-d H:i:s'),
                'salary' => $payrollEmployee ? $payrollEmployee->monthly_salary : null,
                'commutation' => 1,
                'emp_esign' => 2, // signed on filing
                'status' => self::WAITING_SUPERVISOR,
                'remarks_stat' => 0,
                'supervisor' => $employee->supervisor,
                'sup_prefix' => optional(Employee::find($employee->supervisor))->prefix,
                // Kept for the printed form's "Approved by"; the President does not act on a CTO.
                'president' => $setting->suc_pres,
                'pres_prefix' => optional(Employee::find($setting->suc_pres))->prefix,
                'hr' => $setting->hr,
                'hr_prefix' => optional(Employee::find($setting->hr))->prefix,
                'department' => $employee->emp_dept,
            ]);

            foreach ($rows as $row) {
                $cto->ctoDates()->create(['cto_date' => $row['date'], 'hours' => $row['hours']]);
            }

            // First stop is the supervisor (or the OIC standing in).
            Notification::create([
                'empid' => $employee->emp_ID,
                'lapp_id' => $cto->id,
                'esign_id' => $employee->supervisor,
                'category' => 3,
                'utype' => 'supervisor',
                'module' => 'leave',
            ]);
        });

        return redirect()->back()->with('success', 'CTO submitted. It now goes to your immediate supervisor.')->with('leave_tab', 'cto');
    }

    /**
     * Approve or decline a CTO at the supervisor or HR step.
     */
    public function decide(Request $request, LeaveApplication $leaveApplication)
    {
        $data = $request->validate([
            'step' => 'required|in:supervisor,hr',
            'action' => 'required|in:approve,decline',
            'remarks' => 'required_if:action,decline|nullable|string|max:1000',
        ], [
            'remarks.required_if' => 'Give the reason for declining.',
        ]);

        abort_unless($leaveApplication->isCto(), 404);

        return DB::transaction(function () use ($data, $leaveApplication) {
            $cto = LeaveApplication::whereKey($leaveApplication->id)->lockForUpdate()->first();

            if ((int) $cto->history !== 1 || (int) $cto->remarks_stat !== 0) {
                return $this->fail('This CTO application is already closed.', 409);
            }

            return $data['step'] === 'supervisor'
                ? $this->supervisorStep($cto, $data)
                : $this->hrStep($cto, $data);
        });
    }

    private function supervisorStep(LeaveApplication $cto, array $data)
    {
        $user = auth()->guard('employee')->user();
        $signatory = $cto->supervisorSignatory();

        if (!$user || (int) $user->id !== $signatory['id']) {
            return $this->fail($signatory['is_oic']
                ? 'This CTO is for the OIC of the office to act on.'
                : 'Only the immediate supervisor of this employee can act on this CTO.', 403);
        }
        if ((int) $cto->status !== self::WAITING_SUPERVISOR) {
            return $this->fail('This CTO is no longer waiting for the supervisor.', 409);
        }

        $cto->sup_sdate = Carbon::now();
        $this->closeNotice($cto, 3, 'supervisor');

        if ($data['action'] === 'decline') {
            $cto->remarks_stat = 2;
            $cto->remarks_details = $data['remarks'];
            $cto->history = 2;
            $cto->save();
            LeaveNotice::notify($cto, LeaveNotice::DISAPPROVED);

            return $this->ok('CTO declined.');
        }

        // An OIC signs in the supervisor's place.
        if ($signatory['is_oic'] && $signatory['id'] !== $signatory['supervisor']) {
            $cto->oic = $user->id;
        } else {
            $cto->sup_sign = 2;
        }
        $cto->status = self::WAITING_HR;
        $cto->save();

        Notification::create([
            'empid' => $cto->empid,
            'lapp_id' => $cto->id,
            'category' => 2,
            'utype' => 'hr',
            'module' => 'leave',
        ]);
        LeaveNotice::notify($cto, LeaveNotice::SUPERVISOR_APPROVED);

        return $this->ok('CTO approved and forwarded to HR.');
    }

    private function hrStep(LeaveApplication $cto, array $data)
    {
        $user = auth()->guard('web')->user();
        if (!$user || !$user->canAccessPage('leave')) {
            return $this->fail('Only HR accounts with Leave access can act on this CTO.', 403);
        }
        if ((int) $cto->status !== self::WAITING_HR) {
            return $this->fail('This CTO is still waiting for the immediate supervisor.', 409);
        }

        $employee = Employee::where('emp_ID', $cto->empid)->lockForUpdate()->first();
        if ($data['action'] === 'approve' && (float) $cto->cto_hours > (float) ($employee->coc_leave ?? 0)) {
            return $this->fail('The employee no longer has enough Compensatory Overtime Credits for this CTO.', 422);
        }

        $cto->hr_sdate = Carbon::now();
        $this->closeNotice($cto, 2, 'hr');

        if ($data['action'] === 'decline') {
            $cto->remarks_stat = 1;
            $cto->remarks_details = $data['remarks'];
            $cto->history = 2;
            $cto->save();
            LeaveNotice::notify($cto, LeaveNotice::DISAPPROVED);

            return $this->ok('CTO declined.');
        }

        $employee->coc_leave = round((float) $employee->coc_leave - (float) $cto->cto_hours, 3);
        $employee->save();

        $cto->hr_sign = 2;
        $cto->as_of = Carbon::now();
        $cto->status = self::APPROVED;
        $cto->history = 2;
        $cto->save();

        LeaveNotice::notify($cto, LeaveNotice::APPROVED);

        return $this->ok('CTO approved. ' . LeaveApplication::formatHours($cto->cto_hours) . ' hrs deducted from the employee\'s Compensatory Overtime Credits.');
    }

    private function closeNotice(LeaveApplication $cto, int $category, string $utype): void
    {
        Notification::where('lapp_id', $cto->id)->where('module', 'leave')
            ->where('category', $category)->where('utype', $utype)->update(['status' => 1]);
    }

    private function ok(string $message)
    {
        return response()->json(['success' => true, 'message' => $message]);
    }

    private function fail(string $message, int $code)
    {
        return response()->json(['success' => false, 'message' => $message], $code);
    }
}
