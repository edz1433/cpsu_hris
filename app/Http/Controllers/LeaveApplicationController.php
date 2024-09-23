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

        $supemp = Employee::find($employee->supervisor);
        $presemp = Employee::find($setting->suc_pres);
        $hremp = Employee::find($setting->hr);

        $payrollEmployee = PayrollEmployee::where('emp_ID', $request->empid)->first();
        $purpose = $request->leave_purpose;

        if (is_null($employee->supervisor) || $employee->supervisor == 0) {
            return redirect()->back()->withErrors(['error' => 'No Supervisor Assigned']);
        }        

        LeaveApplication::create([
            'empid' => $request->empid,
            'position' => $employee->position,
            'leave_type' => $request->leave_type,
            'leave_purpose' => $purpose,
            'leave_detail' => $firstDetail,
            'date_range' => $request->date_range,
            'days' => $request->days,
            'total_vl' => $employee->vl,
            'total_sl' => $employee->sl,
            'date_filing' => $request->date_filing . ' ' . \Carbon\Carbon::now('Asia/Manila')->format('H:i:s'),
            'salary' => $payrollEmployee->emp_salary,
            'commutation' => ($purpose == 7 || $purpose == 8) ? 2 : 1,
            'supervisor' => $employee->supervisor,
            'sup_prefix' => $supemp->prefix,
            'president' => $setting->suc_pres,
            'pres_prefix' => $presemp->prefix,
            'hr' => $setting->hr,
            'hr_prefix' => $hremp->prefix,
            'department' => $payrollEmployee->emp_dept,
        ]);

        return redirect()->back()->with('success', 'Leave application submitted successfully');
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
        ->orderBy('leave_applications.id', 'desc')
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
        ->orderBy('leave_applications.id', 'desc')
        ->get();
        
   
        $emplalls = Employee::where('emp_status', 1)->get();

        return view("leaves.status", compact('guard', 'setting', 'employee', 'leavesapp', 'leavesapphead', 'emplalls'));
    }

    public function leaveApprove(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:leave_applications,id',
            'by' => 'required|integer|min:1|max:3',
            'day_wpay' => 'nullable|numeric'
        ]);
    
        $leaveApplication = LeaveApplication::find($request->id);
        $currdate = Carbon::now('Asia/Manila')->toDateTimeString();
        $currdate1 = Carbon::now('Asia/Manila')->format('F j, Y h:i A');

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
        
        if ($request->by == 1) {
            $leaveApplication->sup_sdate = $currdate;
        }

        if ($request->by == 2) {
            $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
            if($leaveApplication->leave_type == 3){
                if($leaveApplication->days <= $employee->sl){
                    
                }            
            }

            $leaveApplication->hr_sdate = $currdate;
            $leaveApplication->day_wpay = $request->day_wpay;
        }

        if ($request->by == 3) {
            $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
            $leaveApplication->pres_sdate = $currdate;

            $leavededuct = $leaveApplication->days - $leaveApplication->day_wpay;
            if ($employee && in_array($leaveApplication->leave_type, [1, 2])) {
                $empvl = $employee->vl - $leavededuct;
                
                $earn = $empvl;
                $less = $leaveApplication->days;
                $balance = $empvl - $leaveApplication->days;

                $employee->vl = $empvl;
            }else if($employee && $leaveApplication->leave_type == 3){
                $empsl = $employee->sl - $leavededuct;
                
                $earn = $empsl;
                $less = $leaveApplication->days;
                $balance = $empsl - $leaveApplication->days;

                $employee->sl = $empsl;   
            }

            $leaveApplication->earn = $earn;
            $leaveApplication->less = $less;
            $leaveApplication->balance = $balance;
        }

        $leaveApplication->status = $status;
        $leaveApplication->save();
         
        if ($request->by == 3) {
            $employee->save();
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Leave approved successfully.',
            'datetime' => $currdate1,
        ]);
    }

    public function leaveDisapprove(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'by' => 'required|integer',
            'remarks' => 'required|string',
            'day_wpay' => 'nullable|integer',
        ]);
    
        $leaveApplication = LeaveApplication::find($request->id);
        $currdate = Carbon::now('Asia/Manila')->toDateTimeString();
        $currdate1 = Carbon::now('Asia/Manila')->format('F j, Y h:i A');
        if ($leaveApplication) {
            $leaveApplication->remarks_stat = $request->by;
            $leaveApplication->remarks_details = $request->remarks;

            if ($request->by == 1) {
                $leaveApplication->sup_sdate = $currdate;
            }
    
            if ($request->by == 2) {
                $leaveApplication->hr_sdate = $currdate;
            }
    
            if ($request->by == 3) {
                $leaveApplication->pres_sdate = $currdate;
            }

            $leaveApplication->save();
    
            return response()->json([
                'success' => true,
                'message' => 'Leave disapproved successfully.'
            ]);
        }
    
        return response()->json([
            'success' => false,
            'message' => 'Leave application not found.',
            'datetime' => $currdate1,
        ], 404);
    }

    public function previewLeave($id){
        $guard = $this->getGuard();
        $leaveApplication = LeaveApplication::with(['office:id,office_name,office_abbr'])
        ->join('employees', 'leave_applications.empid', '=', 'employees.emp_ID')
        ->join('employees as sup', 'sup.id', '=', 'leave_applications.supervisor')
        ->join('employees as pres', 'pres.id', '=', 'leave_applications.president')
        ->select('leave_applications.*', 
            'leave_applications.id as lid', 
            'employees.lname', 
            'employees.fname', 
            'employees.mname', 
            'employees.suffix',             
            'sup.lname as supervisor_lname', 
            'sup.fname as supervisor_fname', 
            'sup.mname as supervisor_mname', 
            'sup.suffix as supervisor_suffix', 
            'sup.prefix as supervisor_prefix',
            'pres.lname as president_lname', 
            'pres.fname as president_fname', 
            'pres.mname as president_mname', 
            'pres.suffix as president_suffix', 
            'pres.prefix as president_prefix',
        )
        ->where('leave_applications.id', $id)
        ->first();
    
        $customPaper = array(0, 0, 595.28, 841.89);
        $pdf = \PDF::loadView('leaves.generate-leave', compact('leaveApplication'))->setPaper($customPaper, 'portrait');
        
        $pdf->setOption('margin-top', 0);
        $pdf->setOption('margin-right', 0);
        $pdf->setOption('margin-bottom', 0);
        $pdf->setOption('margin-left', 0);

        $pdf->setCallbacks([
            'before_render' => function ($domPdf) {
                $domPdf->getCanvas()->page_text(10, 10, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, array(0, 0, 0));
            },
        ]);

        $pdf->render();

        return $pdf->stream();
    }
    
    public function leaveLive($id = null)
    {
        $guard = $this->getGuard();
        $empid = ($guard == "web") ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);
    
        return response()->json([
            'vl' => $employee->vl,
            'sl' => $employee->sl 
        ]);
    }
    
    public function eSign()
    {
        $guard = $this->getGuard();

        return view('leaves.esign', compact('guard'));
    }

    public function uploadAndSign(Request $request)
    {
        // Validate the request (if needed)
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:2048',
        ]);

        // Path to the PDF file
        $filePath = public_path('Uploads/dtr.pdf');

        // Initialize Guzzle Client
        $client = new Client();

        // Example of signing the document (adjust the URL and payload as needed)
        $response = $client->post('https://api.example.com/esign', [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => 'dtr.pdf',
                ],
                // Additional form fields can be added here
                [
                    'name' => 'user_id',
                    'contents' => $guard->id, // or any other identifier
                ],
            ],
        ]);

        // Handle the response
        $responseData = json_decode($response->getBody(), true);
        
        // Check for success and redirect or return an appropriate response
        if ($response->getStatusCode() == 200) {
            return redirect()->back()->with('success', 'Document signed successfully!');
        } else {
            return redirect()->back()->with('error', 'Failed to sign the document.');
        }
    }
}
