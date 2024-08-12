<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Employee;
use App\Models\FamilyBg;
use App\Models\EducBg;
use App\Models\Eligibility;
use App\Models\WorkExperience;
use App\Models\VoluntaryWork;

class VoluntaryWorkController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }
    
    public function columnStat($empid){
        $familyBg = FamilyBg::where('empid', $empid)->first();
        $educBg = EducBg::where('empid', $empid)->first();
        $eligibility = Eligibility::where('empid', $empid)->get();
        $workexperience = WorkExperience::where('empid', $empid)->get();
        $voluntaryworks = VoluntaryWork::where('empid', $empid)->get();
        $columnstatus = [
            'colfamstat' => $familyBg->famhasAnyValue(),
            'coleducstat' => $educBg->educhasAnyValue(),
            'eligibility' => $eligibility,
            'workexperience' => $workexperience,
            'voluntaryworks' => $voluntaryworks,
        ];
        
        return $columnstatus;
    }

    public function voluntaryworks($id = null){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);
        $voluntaryworks = VoluntaryWork::where('empid', $employee->emp_ID)->get();
        $columnstatus = $this->columnStat($employee->emp_ID);
        
        return view("emp.voluntary-work", compact('guard', 'empid', 'employee', 'voluntaryworks', 'columnstatus'));
    }

    public function voluntaryworksCreate(Request $request)
    {
        $request->validate([
            'empid' => 'required',
            'org_name' => 'required',
            'inc_date1' => 'required',
            'inc_date2' => 'required',
            'num_hours' => 'nullable',
            'position' => 'required',
        ]);

        $salary = $request->input('salary');
        if (!is_null($salary)) {
            $salary = str_replace(',', '', $salary);
        }

        VoluntaryWork::create([
            'empid' => $request->input('empid'),
            'org_name' => $request->input('org_name'),
            'inc_date1' => $request->input('inc_date1'),
            'inc_date2' => $request->input('inc_date2'),
            'num_hours' => $request->input('num_hours'),
            'position' => $request->input('position'),
        ]);

        return redirect()->back()->with('success', 'Voluntary work added successfully!');
    }   

    public function voluntaryworksEdit($id, $eid)
    {
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);
        $voluntaryworks = VoluntaryWork::where('empid', $employee->emp_ID)->get();
        $voluntaryworksedit = VoluntaryWork::where('id', $eid)->where('empid', $employee->emp_ID)->first();
        $columnstatus = $this->columnStat($employee->emp_ID);
        return view('emp.voluntary-work', compact('guard', 'empid', 'employee', 'voluntaryworks', 'voluntaryworksedit', 'columnstatus'));
    }

    public function workexperienceUpdate(Request $request, $id)
    {
        $request->validate([
            'empid' => 'required',
            'org_name' => 'required',
            'inc_date1' => 'required',
            'inc_date2' => 'required',
            'num_hours' => 'nullable',
            'position' => 'required',
        ]);

        $voluntaryworks = VoluntaryWork::findOrFail($id);
        $voluntaryworks->update([
            'org_name' => $request->input('org_name'),
            'inc_date1' => $request->input('inc_date1'),
            'inc_date2' => $request->input('inc_date2'),
            'num_hours' => $request->input('num_hours'),
            'position' => $request->input('position'),
        ]);

        return redirect()->back()->with('success', 'Updated successfully!');
    }

    public function workDelete($id)
    {
        $voluntaryworks = VoluntaryWork::find($id);
        
        if ($voluntaryworks) {
            $filePath = public_path('storage/' . $voluntaryworks->attachment);
    
            $voluntaryworks->delete();
    
            return response()->json([
                'status' => 200,
                'message' => "Deleted Successfully",
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "Record not found",
            ]);
        }
    }
}
