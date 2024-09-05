<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\LeaveCredit;
use Carbon\Carbon;

class LeaveCreditController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }  

    public function leavesRead($id = null){
        $emplalls = Employee::all();
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);
        $leaves = LeaveCredit::where('empid', $employee->emp_ID)
        ->join('users', 'leave_credits.add_by', '=', 'users.id')
        ->select('leave_credits.*', 'users.fname', 'users.mname', 'users.lname')
        ->orderBy('leave_credits.created_at', 'desc')
        ->get();    

        return view('leaves.emp-leaves', compact('leaves', 'guard', 'employee', 'emplalls'));
    }

    public function leavesReadEmp(){
        $guard = $this->getGuard();
        $empid = auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);
        $leaves = LeaveCredit::where('empid', $employee->emp_ID)
        ->join('users', 'leave_credits.add_by', '=', 'users.id')
        ->select('leave_credits.*', 'users.fname', 'users.mname', 'users.lname')
        ->orderBy('leave_credits.created_at', 'desc')
        ->get();    

        return view('leaves.emp-leaves', compact('leaves', 'guard', 'employee'));
    }

    public function leavesCreate(Request $request)
    {
        $authid = auth()->user()->id;
        $currentDate = Carbon::now()->format('Y-m');
        $request->validate([
            'empid' => 'required|exists:employees,id',
            'sl' => 'required|numeric|min:0',
            'vl' => 'required|numeric|min:0',
        ]);
    
        $employee = Employee::find($request->empid);
    
        if ($employee) {
            $employee->sl += $request->sl;
            $employee->vl += $request->vl;

            $records = LeaveCredit::where('empid', $employee->emp_ID)
            ->where('date', $request->date)
            ->orderBy('created_at', 'asc') 
            ->get();

            $skipFirstRow = $records->skip(1)->first();
            if (!$skipFirstRow) {
                $employee->save();
                
                LeaveCredit::create([
                    'empid' => $employee->emp_ID,
                    'days' => $request->days,
                    'earn_sl' => $request->sl,
                    'earn_vl' => $request->sl,
                    'remarks' => $request->remarks,
                    'date' =>  isset($request->date) ? $request->date : $currentDate,
                    'add_by' => $authid,
                ]);
            }else{
                return redirect()->back()->with('error', ' Already exist');
            }

        } else {
            return redirect()->back()->with('error', 'Employee not found.');
        }
    
        return redirect()->back()->with('success', 'Save successfully.');
    }

    public function leavesEdit(Request $request){
        $leavecredit = LeaveCredit::find($request->id);

        return response()->json([
            'data'=> $leavecredit,
        ]);
    }

    public function leavesUpdate(Request $request)
    {
        $authid = auth()->user()->id;
        $currentDate = Carbon::now()->format('Y-m');
        $request->validate([
            'empid' => 'required|exists:employees,id',
            'sl' => 'required|numeric|min:0',
            'vl' => 'required|numeric|min:0',
        ]);
    
        $employee = Employee::find($request->empid);
    
        if ($employee) {
            $employee->sl += $request->sl;
            $employee->vl += $request->vl;

            $records = LeaveCredit::where('empid', $employee->emp_ID)
            ->where('date', $request->date)
            ->orderBy('created_at', 'asc') 
            ->get();

            $skipFirstRow = $records->skip(1)->first();
            if (!$skipFirstRow) {
                $employee->save();
                
                LeaveCredit::where('id', $request->lcid)
                ->update([
                    'days' => $request->days,
                    'earn_sl' => $request->sl,
                    'earn_vl' => $request->vl,
                    'remarks' => $request->remarks,
                    'date' => isset($request->date) ? $request->date : $currentDate,
                    'add_by' => $authid,
                ]);

            }else{
                return redirect()->back()->with('error', ' Already exist');
            }

        } else {
            return redirect()->back()->with('error', 'Employee not found.');
        }
    
        return redirect()->back()->with('success', 'Save successfully.');
    }

    public function leavesDelete($id, $empid){
        $emp = LeaveCredit::find($id);
        $emp->delete();

        return response()->json([
            'status'=>200,
            'message'=>"Deleted Successfully",
        ]);
    }
}
