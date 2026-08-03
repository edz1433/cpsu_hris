<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Employee;
use App\Models\Eligibility;
use App\Models\WorkExperience;
use App\Models\VoluntaryWork;
use App\Models\LearningDev;
use App\Models\LeaveApplication;

class PendingController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function readPending($type, $cat = null) {
        $guard = $this->getGuard();

        $leaveappCount = LeaveApplication::where('emp_esign', '=', 0)->where('history', 1)->where('status', 1)->count('empid');
        $eliCount = Eligibility::where('status', 0)->count();
        $workexpCount = WorkExperience::where('status', 0)->count();
        $learDevCount = LearningDev::where('status', 0)->count();
        $volWorkCount = VoluntaryWork::where('status', 0)->count();

        $employees = [];
        
        switch ($type) {
            case '1':
                $employees = LeaveApplication::join('employees as emp', 'emp.emp_ID', '=', 'leave_applications.empid')
                ->join('employees as hr', 'hr.id', '=', 'leave_applications.hr')
                ->join('employees as sup', 'sup.id', '=', 'leave_applications.supervisor')
                ->join('employees as sucpres', 'sucpres.id', '=', 'leave_applications.president')
                ->select(
                    'leave_applications.*',
                    'emp.emp_ID as empid',
                    'emp.id as employid',
                    'emp.lname as employee_lname',
                    'emp.fname as employee_fname',
                    'emp.mname as employee_mname',
                    'emp.suffix as employee_suffix',
                    'hr.lname as hr_lname',
                    'hr.fname as hr_fname',
                    'hr.mname as hr_mname',
                    'hr.suffix as hr_suffix',
                    'sup.lname as supervisor_lname',
                    'sup.fname as supervisor_fname',
                    'sup.mname as supervisor_mname',
                    'sup.suffix as supervisor_suffix',
                    'sucpres.lname as sucpres_lname',
                    'sucpres.fname as sucpres_fname',
                    'sucpres.mname as sucpres_mname',
                    'sucpres.suffix as sucpres_suffix',
                );
        
                // Add a filter for $cat
                if ($cat !== null && in_array($cat, [1, 0.1, 0.2])) {
                    if($cat == 0.1){
                        $employees = $employees->where('leave_applications.emp_esign', '=', 0);
                    }
                    if($cat == 0.2){
                        $employees = $employees->where('leave_applications.emp_esign', '=', 1);
                    }
                    if($cat == 1){
                        $employees = $employees->where('leave_applications.emp_esign', '=', 2);
                    }
                    $employees = $employees->where('leave_applications.status', '=', 1);
                    $employees = $employees->whereIn('leave_applications.history', [0, 1]);
                }
                elseif ($cat !== null && in_array($cat, [2, 3])) {
                    $employees = $employees->where('leave_applications.status', '=', $cat);
                    $employees = $employees->whereIn('leave_applications.history', [0, 1]);
                }
                elseif ($cat == 4) {
                    $employees = $employees->where('leave_applications.status', '=', $cat);
                    $employees = $employees->where('leave_applications.history', '=', 2);
                }
                elseif ($cat == 5) {
                    $employees = $employees->where('leave_applications.history', 2);
                    $employees = $employees->where('leave_applications.remarks_stat', '!=', 0);
                }else{
                    $employees = $employees->where('leave_applications.history', '!=', 2);
                }
                
                $employees = $employees
                    ->orderBy('leave_applications.id', 'desc')
                    ->get();
                       
                break;            
    
            case '2':
                $empids = Eligibility::where('status', 0)
                    ->get()->pluck('empid')->unique()->values()->toArray();
                break;
    
            case '3':
                $empids = WorkExperience::where('status', 0)
                    ->pluck('empid')->unique()->values()->toArray();
                break;
    
            case '4':
                $empids = VoluntaryWork::where('status', 0)
                    ->pluck('empid')->unique()->values()->toArray();
                break;
    
            case '5':
                $empids = LearningDev::where('status', 0)
                    ->pluck('empid')->unique()->values()->toArray();
                break;
                
            default:
                return redirect()->route('');
        }

        if($type != 1){
            $employees = Employee::whereIn('emp_ID', $empids)->get();
        }

        return view('pending.index', compact('guard', 'cat', 'type', 'employees', 'eliCount', 'workexpCount', 'learDevCount', 'volWorkCount', 'leaveappCount'));
    }

    public function leaveUndo(Request $request, $id = null)
    {
        if ($id === null) {
            $id = $request->id;
        }

        $leaveApplication = LeaveApplication::find($id);

        if (!$leaveApplication) {
            return response()->json([
                'success' => false,
                'message' => 'Leave application not found.'
            ], 404);
        }

        $leaveApplication->history = 1;
        $leaveApplication->status = 3;
        $leaveApplication->save();

        return response()->json([
            'success' => true,
            'message' => 'Leave updated successfully.'
        ]);
    }

}
