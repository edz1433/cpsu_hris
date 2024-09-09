<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\PayrollEmployee;
use App\Models\LeaveCredit;
use App\Models\LeaveApplication;
use App\Models\Setting;
use Carbon\Carbon;

class LeaveApplicationController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }  

    public function LeaveAppCreate(Request $request)
    {
        $validatedData = $request->validate([
            'empid' => 'required|exists:employees,emp_ID',
            'date_range' => 'required|string',
        ]);

        $leaveDetails = array_filter($request->input('leave_detail'));

        $firstDetail = reset($leaveDetails);
        
        $setting = Setting::first();

        $employee = Employee::where('emp_ID', $request->empid)->first();
        $payrollEmployee = PayrollEmployee::where('emp_ID', $request->empid)->first();
        $purpose = $request->leave_purpose;

        if (is_null($employee->supervisor) || $employee->supervisor == 0) {
            return redirect()->back()->withErrors(['error' => 'No Supervisor Assigned']);
        }        

        LeaveApplication::create([
            'empid' => $request->empid,
            'leave_type' => $request->leave_type,
            'leave_purpose' => $purpose,
            'leave_detail' => $firstDetail,
            'date_range' => $request->date_range,
            'days' => $request->days,
            'total_vl' => $employee->vl,
            'total_sl' => $employee->sl,
            'date_filing' => $request->date_filing,
            'salary' => $payrollEmployee->emp_salary,
            'commutation' => ($purpose == 7 || $purpose == 8) ? 2 : 1,
            'supervisor' => $employee->supervisor,
            'president' => $setting->suc_pres,
            'hr' => $setting->hr,
        ]);

        return redirect()->back()->with('success', 'Leave application saved successfully');
    }

    public function leaveStatus($id = null){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);

        $leavesapp = LeaveApplication::where('empid', $employee->emp_ID)
        ->join('employees as sup', 'sup.id', '=', 'leave_applications.supervisor')
        ->select(
            'leave_applications.*', 
            'sup.lname as supervisor_lname', 
            'sup.fname as supervisor_fname', 
            'sup.mname as supervisor_mname', 
            'sup.suffix as supervisor_suffix'
        )
        ->get();


        $setting = Setting::join('employees as hr', 'hr.id', '=', 'settings.hr')
        ->join('employees as sucpres', 'sucpres.id', '=', 'settings.suc_pres')
        ->select(
            'settings.*', 
            'hr.lname as hr_lname', 
            'hr.fname as hr_fname', 
            'hr.mname as hr_mname', 
            'hr.suffix as hr_suffix',
            'sucpres.lname as sucpres_lname', 
            'sucpres.fname as sucpres_fname', 
            'sucpres.mname as sucpres_mname', 
            'sucpres.suffix as sucpres_suffix'
        )
        ->first();

        $leavesapphead = LeaveApplication::join('employees as emp', 'emp.emp_ID', '=', 'leave_applications.empid')
            ->join('employees as sup', 'sup.id', '=', 'leave_applications.supervisor');
        
        if ($setting->suc_pres !== auth()->guard($guard)->user()->id) {
            $leavesapphead->where('leave_applications.supervisor', auth()->guard($guard)->user()->id);
        }else{
            $leavesapphead->whereIn('leave_applications.status', [3]);
        }

        $leavesapphead = $leavesapphead->select(
            'leave_applications.*', 
            'emp.lname as employee_lname', 
            'emp.fname as employee_fname', 
            'emp.mname as employee_mname', 
            'emp.suffix as employee_suffix',
            'sup.lname as supervisor_lname', 
            'sup.fname as supervisor_fname', 
            'sup.mname as supervisor_mname', 
            'sup.suffix as supervisor_suffix'
        )
        ->get();
        
   
        $emplalls = Employee::where('emp_status', 1)->get();

        return view("leaves.status", compact('guard', 'setting', 'employee', 'leavesapp', 'leavesapphead', 'emplalls'));
    }

    public function approveLeave(Request $request)
    {
        $leaveApplication = LeaveApplication::find($request->id);

        if ($leaveApplication) {
            $status = 0;
            switch ($request->by) {
                case 1:
                    $status = 2;
                    break;
                case 2:
                    $status = 3;
                    break;
                case 3:
                    $status = 4;
                    break;
            }
            
            $leaveApplication->status = $status;
            $leaveApplication->save();

            return response()->json(['success' => true, 'message' => 'Leave approved successfully.']);
        }

        return response()->json(['success' => false, 'message' => 'Leave not found.'], 404);
    }
}
