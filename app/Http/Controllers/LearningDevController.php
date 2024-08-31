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
use App\Models\LearningDev;
use App\Models\OtherInfo;
use App\Models\InfoQuestion;
use App\Models\PdsReference;
use App\Models\GovId;

class LearningDevController extends Controller
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
        $learningdev = LearningDev::where('empid', $empid)->get();
        $otherinfo = OtherInfo::where('empid', $empid)->first();
        $infoquestion = InfoQuestion::where('empid', $empid)->first();
        $references = PdsReference::where('empid', $empid)->first();
        $govids= GovId::where('empid', $empid)->first();
        
        $columnstatus = [
            'colfamstat' => $familyBg->famhasAnyValue(),
            'coleducstat' => $educBg->educhasAnyValue(),
            'eligibility' => $eligibility,
            'workexperience' => $workexperience,
            'voluntaryworks' => $voluntaryworks,
            'learningdev' => $learningdev,
            'colotherinfo' => $otherinfo->otherinfoAnyValue(),
            'colinfoquestion' => $infoquestion->infoquestionValue(),
            'colreferences' => $references->referencesValue(),
            'colgovids' => $govids->govidsValue(),
        ];

        return $columnstatus;
    }

    public function learningdev($id = null){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);
        $learningdev = LearningDev::where('empid', $employee->emp_ID)->get();
        $columnstatus = $this->columnStat($employee->emp_ID);
        
        return view("emp.learning-dev", compact('guard', 'empid', 'employee', 'learningdev', 'columnstatus'));
    }

    public function learningdevCreate(Request $request)
    {
        $request->validate([
            'empid' => 'required',
            'learning_dev' => 'required',
            'inc_date1' => 'required',
            'inc_date2' => 'required',
            'num_hours' => 'required',
            'types' => 'required',
            'conducted' => 'required',
        ]);

        LearningDev::create([
            'empid' => $request->input('empid'),
            'learning_dev' => $request->input('learning_dev'),
            'inc_date1' => $request->input('inc_date1'),
            'inc_date2' => $request->input('inc_date2'),
            'num_hours' => $request->input('num_hours'),
            'types' => $request->input('types'),
            'conducted' => $request->input('conducted'),
        ]);

        return redirect()->back()->with('success', 'Added successfully!');
    }   

    public function learningdevEdit($id, $eid)
    {
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);
        $learningdev = LearningDev::where('empid', $employee->emp_ID)->get();
        $learningdevedit = LearningDev::where('id', $eid)->where('empid', $employee->emp_ID)->first();
        $columnstatus = $this->columnStat($employee->emp_ID);
        return view('emp.learning-dev', compact('guard', 'empid', 'employee', 'learningdev', 'learningdevedit', 'columnstatus'));
    }

    public function learningdevUpdate(Request $request, $id)
    {
        $request->validate([
            'empid' => 'required',
            'learning_dev' => 'required',
            'inc_date1' => 'required',
            'inc_date2' => 'required',
            'num_hours' => 'required',
            'types' => 'required',
            'conducted' => 'required',
        ]);

        $learningdev = LearningDev::findOrFail($id);
        $learningdev->update([
            'learning_dev' => $request->input('learning_dev'),
            'inc_date1' => $request->input('inc_date1'),
            'inc_date2' => $request->input('inc_date2'),
            'num_hours' => $request->input('num_hours'),
            'types' => $request->input('types'),
            'conducted' => $request->input('conducted'),
        ]);

        return redirect()->back()->with('success', 'Updated successfully!');
    }

    public function learningdevDelete($id)
    {
        $learningdev = LearningDev::find($id);
        
        if ($learningdev) {
            $learningdev->delete();
    
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
