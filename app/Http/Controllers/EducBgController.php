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

class EducBgController extends Controller
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

    public function educbg($id = null){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);
        $educBg = EducBg::where('empid', $employee->emp_ID)->first();
        $columnstatus = $this->columnStat($employee->emp_ID);
        
        return view("emp.educational-bg", compact('guard', 'empid', 'employee', 'educBg', 'columnstatus'));
    }

    public function educBgUpdate(Request $request){
        $employee = Employee::find($request->id);
        $educbg = EducBg::where("empid", $employee->emp_ID)->first();
        $column = $request->column;
        $value = $request->value;

        $educbg->update([
            $column => $value,
        ]);
        
        return response()->json(['success' => true]);
    }

    public function educBgUpdateArray(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'schools' => 'required|array',
            'degrees' => 'required|array',
            'periods' => 'required|array',
            'levels' => 'required|array',
            'years' => 'required|array',
            'honors' => 'required|array',
            'schools.*' => 'nullable|string',
            'degrees.*' => 'nullable|string',
            'periods.*' => 'nullable|string',
            'levels.*' => 'nullable|string',
            'years.*' => 'nullable|date',
            'honors.*' => 'nullable|string',
        ]);
        
        $empid = $request->input('empid'); 
        $educbg = EducBg::where("empid", '=', $empid)->first();
        
        $schools = $request->input('schools');
        $degrees = $request->input('degrees');
        $periods = $request->input('periods');
        $levels = $request->input('levels');
        $years = $request->input('years');
        $honors = $request->input('honors');

        // Update the 'educBg' table
        $educbg->update([
            'coll_school' => implode(',', $schools),
            'coll_course' => implode(',', $degrees),
            'coll_period' => implode(',', $periods),
            'coll_level' => implode(',', $levels),
            'coll_grad' => implode(',', $years),
            'coll_honor' => implode(',', $honors),
        ]);
        
        return response()->json(['success' => true]);
    }

}
