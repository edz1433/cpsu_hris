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
    
        $leaveApplication = LeaveApplication::create([
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
    
        $this->genApplication($leaveApplication->id);
        
        return redirect()->back()->with('success', 'Submitted successfully');
    }
    
    public function leaveStatus($id = null){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);
        
        $leavesapp = LeaveApplication::where('empid', $employee->emp_ID)
        ->join('employees as sup', 'sup.id', '=', 'leave_applications.supervisor')
        ->join('employees as emp', 'emp.emp_ID', '=', 'leave_applications.empid')
        ->select(
            'leave_applications.*', 
            'emp.id as employid',
            'sup.lname as supervisor_lname', 
            'sup.fname as supervisor_fname', 
            'sup.mname as supervisor_mname', 
            'sup.suffix as supervisor_suffix'
        )
        ->orderBy('leave_applications.id', 'desc')
        ->where('leave_applications.history', 1)
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
            'sucpres.suffix as sucpres_suffix',
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
            'emp.id as employid', 
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
        ->where('leave_applications.history', 1)
        ->get();

        // dd($leavesapphead);
        
        $emplalls = Employee::where('emp_status', 1)->get();

        return view("leaves.status", compact('guard', 'setting', 'employee', 'leavesapp', 'leavesapphead', 'emplalls', 'empid'));
    }

    public function leaveWpay(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'day_wpay' => 'required',
        ]);
    
        $leaveApplication = LeaveApplication::find($request->id);
        $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
    
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }
    
        $leaveApplication->hr_sdate = Carbon::now();
        $leaveApplication->day_wpay = $request->day_wpay;
        $daysdeduct = $leaveApplication->days - $request->day_wpay;
    
        if ($leaveApplication->leave_type == 3) {
            $employee->sl = $employee->sl ?? 0;
            $employee->vl = $employee->vl ?? 0;
            
            if ($daysdeduct > $employee->sl) {
                $remainingDays = $daysdeduct - $employee->sl;
                
                if ($remainingDays > $employee->vl) {
                    return response()->json(['error' => 'Insufficient leave credits'], 400);
                }
            }else{

            }
        }

        $originalPath = $leaveApplication->gen_app;
        
        if (file_exists(public_path($originalPath)) && !is_dir(public_path($originalPath))) {
            unlink(public_path($originalPath));
        }
        
        $leaveApplication->emp_esign = 1;
        $leaveApplication->save();

        $this->genApplication($leaveApplication->id);
    
        return response()->json([
            'success' => true,
            'message' => 'Leave approved successfully.',
            'datetime' => now(),
            'withpay' =>  $daysdeduct,
            'withoutpay' => $request->day_wpay,
        ]);
    }
    
    public function leaveApprove(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:leave_applications,id',
            'by' => 'required|integer|min:0|max:3',
            'day_wpay' => 'nullable|numeric',
            'file' => 'required|file|mimes:pdf'
        ]);
        
        $leaveApplication = LeaveApplication::find($request->id);
        $currdate = Carbon::now('Asia/Manila')->toDateTimeString();
        $currdate1 = Carbon::now('Asia/Manila')->format('F j, Y h:i A');
    
        $status = 1;
        switch ($request->by) {
            case 0:
                $emp_esign = 2;
                break;
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
    
        if ($request->hasFile('file')) {
            $originalPath = $leaveApplication->gen_app;
            $filenameArray = explode('/', $originalPath);
            $filename = end($filenameArray);

            if (file_exists(public_path($originalPath)) && !is_dir(public_path($originalPath))) {
                unlink(public_path($originalPath));
            }
    
            $path = public_path('Uploads/Leaveapplication');
    
            $file = $request->file('file');
            $file->move($path, $filename);
        }

        if($request->by == 0){
            $leaveApplication->emp_esign = $emp_esign;
        }
        
        if($request->by == 1){
            $leaveApplication->sup_sdate = Carbon::now();
        }

        if($request->by == 2){
            $leaveApplication->hr_sdate = Carbon::now();
            // $leaveApplication->day_wpay = $request->day_wpay;

            // $daysdeduct = $leaveApplication->days - $request->day_wpay;

            // $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();
        
            // if ($leaveApplication->leave_type == 1) {
            //     $employee->vl = $employee->vl ?? 0;
            //     $employee->sl = $employee->sl ?? 0;
        
            //     if ($daysdeduct > $employee->vl) {
            //         $remainingDays = $daysdeduct - $employee->vl;
        
            //         if ($remainingDays > $employee->sl) {
            //             return response()->json(['error' => 'Insufficient leave credits'], 400);
            //         } 
            //     } 
            // }
        }

        if ($request->by == 3) {
            $leaveApplication->pres_sdate = Carbon::now();
            $employee = Employee::where('emp_ID', $leaveApplication->empid)->first();

            $daysdeduct = ($leaveApplication->days ?? 0) - ($leaveApplication->day_wpay ?? 0);

            $employee->vl = $employee->vl ?? 0;
            $employee->sl = $employee->sl ?? 0;
    
            if ($leaveApplication->leave_type == 3) {
                $employee->sl = $employee->sl ?? 0;
                $employee->vl = $employee->vl ?? 0;
                
                if ($daysdeduct > $employee->sl) {
                    $remainingDays = $daysdeduct - $employee->sl;
                    
                    if ($remainingDays > $employee->sl) {
                        $employee->sl -= $employee->sl;
                        $employee->vl -= $remainingDays;
                    }
                }else if($daysdeduct <= $employee->sl){
                    $employee->sl -= $daysdeduct;
                }
            }else{
                $employee->vl -= $daysdeduct;
            }
        
            $employee->save();
        
            $leaveApplication->history = 2;
            $leaveApplication->save();
        }        
        
        $leaveApplication->status = $status ?? 1;
        $leaveApplication->save();
    
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

    public function leaveReturn(Request $request, $id = null)
    {
        $validatedData = $request->validate([
            'to' => 'required|integer|min:0|max:3',
        ]);
        
        if ($id === null) {
            $id = $request->id;
        }
    
        $leaveApplication = LeaveApplication::find($id);
        $originalPath = $leaveApplication->gen_app;
    
        if (!$leaveApplication) {
            return response()->json([
                'success' => false,
                'message' => 'Leave application not found.'
            ], 404);
        }
    
        switch ($request->to) {
            case 1:
                $leaveApplication->emp_esign = 1;
                if (file_exists(public_path($originalPath)) && !is_dir(public_path($originalPath))) {
                    unlink(public_path($originalPath));
                }
                $this->genApplication($id);
                break;
            case 2:
                $leaveApplication->status = 1;
                break;
            case 3:
                $leaveApplication->status = 2;
                break;
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status value.'
                ], 400);
        }
        
        $leaveApplication->save();
    
        return response()->json([
            'success' => true,
            'message' => 'Leave updated successfully.'
        ]);
    }    

    public function genApplication($id) {
        $leaveApplication = LeaveApplication::with(['office:id,office_name,office_abbr'])
            ->join('employees', 'leave_applications.empid', '=', 'employees.emp_ID')
            ->join('employees as sup', 'sup.id', '=', 'leave_applications.supervisor')
            ->join('employees as pres', 'pres.id', '=', 'leave_applications.president')
            ->join('employees as hr', 'hr.id', '=', 'leave_applications.hr')
            ->select('leave_applications.*', 
                'leave_applications.id as lid', 
                'employees.lname', 'employees.fname', 'employees.mname', 'employees.suffix',             
                'sup.lname as supervisor_lname', 'sup.fname as supervisor_fname', 'sup.mname as supervisor_mname', 
                'sup.suffix as supervisor_suffix', 'sup.prefix as supervisor_prefix',
                'hr.lname as hr_lname', 'hr.fname as hr_fname', 'hr.mname as hr_mname', 
                'hr.suffix as hr_suffix',
                'pres.lname as president_lname', 'pres.fname as president_fname', 'pres.mname as president_mname', 
                'pres.suffix as president_suffix', 'pres.prefix as president_prefix'
            )
            ->where('leave_applications.id', $id)->first();
        
        $customPaper = [0, 0, 595.28, 841.89];
        $pdf = \PDF::loadView('leaves.generate-leave', compact('leaveApplication'))->setPaper($customPaper, 'portrait')
            ->setOption('margin-top', 0)
            ->setOption('margin-right', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0)
            ->setCallbacks([
                'before_render' => function ($domPdf) {
                    $domPdf->getCanvas()->page_text(10, 10, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, [0, 0, 0]);
                }
            ]);
    
        $directoryPath = public_path('Uploads/Leaveapplication');
        $randomNumber = mt_rand(100000, 999999);
        
        $fileName = $randomNumber . '_leave_application_' . $id . '.pdf';
        $filePath = $directoryPath . '/' . $fileName;

        if (!file_exists($directoryPath)) {
            mkdir($directoryPath, 0777, true);
        }

        $leaveApplication->gen_app = 'Uploads/Leaveapplication/' . $fileName;
        $leaveApplication->save();

        $pdf->save($filePath);

        return $filePath;
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

    public function historyRead($id = null){
        $guard = $this->getGuard();
        $authid = auth()->guard($guard)->user()->id;
        
        $empid = ($guard == "web") ? $id : $authid;
        
        $employee = Employee::find($empid);
        $emplalls = Employee::where('emp_status', 1)->get();
        
        $settings = Setting::join('employees as hr', 'hr.id', '=', 'settings.hr')
        ->join('employees as sucpres', 'sucpres.id', '=', 'settings.suc_pres')
        ->select(
            'settings.*', 
            'hr.id as hrid', 
            'sucpres.id as sucpresid', 
        )
        ->first();

        

        if($guard == "web"){

            $leaveApplication = LeaveApplication::where('leave_applications.history', 2)
            ->where('leave_applications.empid', $employee->emp_ID)
            ->orderBy('leave_applications.date_filing', 'desc')
            ->get();

            $leaveApplication1 = [];

        }else{

            $leaveApplication = LeaveApplication::where('leave_applications.history', 2)
            ->where('leave_applications.empid', $employee->emp_ID)
            ->orderBy('leave_applications.date_filing', 'desc')
            ->get();

            $leaveApplication1 = LeaveApplication::where('leave_applications.history', 2)
            ->where('leave_applications.supervisor', $empid)
            ->orderBy('leave_applications.date_filing', 'desc')
            ->get();

        }

        return view('leaves.history', compact('guard', 'empid', 'employee', 'emplalls', 'leaveApplication', 'leaveApplication1'));
    }

    public function getPdfPath(Request $request)
    {
        $leaveId = $request->input('id');

        $leave = LeaveApplication::find($leaveId);

        if ($leave && $leave->gen_app) {
            return response()->json(['path' => asset($leave->gen_app)]);
        }

        return response()->json(['error' => 'PDF not found'], 404);
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
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:2048',
        ]);

        $filePath = public_path('Uploads/dtr.pdf');

        $client = new Client();

        $response = $client->post('https://api.example.com/esign', [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => 'dtr.pdf',
                ],
                [
                    'name' => 'user_id',
                    'contents' => $guard->id,
                ],
            ],
        ]);

        $responseData = json_decode($response->getBody(), true);
        
        if ($response->getStatusCode() == 200) {
            return redirect()->back()->with('success', 'Document signed successfully!');
        } else {
            return redirect()->back()->with('error', 'Failed to sign the document.');
        }
    }
}
