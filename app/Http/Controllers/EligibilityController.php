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

class EligibilityController extends Controller
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

    public function eligibility($id = null){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);
        $eligibility = Eligibility::where('empid', $employee->emp_ID)->get();
        $columnstatus = $this->columnStat($employee->emp_ID);
        
        return view("emp.eligibility", compact('guard', 'empid', 'employee', 'eligibility', 'columnstatus'));
    }

    public function eligibilityEdit($id = null, $eid){
        $guard = $this->getGuard();
        $empid = ($id) ? $id : auth()->guard($guard)->user()->id;
        $employee = Employee::find($empid);
        $eligibility = Eligibility::where('empid', $employee->emp_ID)->get();
        $eligibilityedit = Eligibility::where('id', $eid)->where('empid', $employee->emp_ID)->first();
        $columnstatus = $this->columnStat($employee->emp_ID);
        
        return view("emp.eligibility", compact('guard', 'empid', 'employee', 'eligibility', 'eligibilityedit', 'columnstatus'));
    }

    public function eligibilityCreate(Request $request)
    {
        $request->validate([
            'career_eligible' => 'required',
            'rating' => 'required',
            'date_exam' => 'required',
            'place_exam' => 'required',
            'number' => 'required',
            'date_valid' => 'required',
            'attachment' => 'required', 
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $originalName = pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $attachment->getClientOriginalExtension();
            $randomNumber = rand(10000, 99999);
            $newFileName = $randomNumber . '_' . $originalName . '.' . $extension;
            $attachmentPath = $attachment->storeAs('Eligibility', $newFileName, 'public');
        }

        Eligibility::create([
            'empid' => $request->input('empid'),
            'career_eligible' => $request->input('career_eligible'),
            'rating' => $request->input('rating'),
            'date_exam' => $request->input('date_exam'),
            'place_exam' => $request->input('place_exam'),
            'number' => $request->input('number'),
            'date_valid' => $request->input('date_valid'),
            'attachment' => $attachmentPath,
        ]);

        return redirect()->back()->with('success', 'Eligibility submitted successfully.');
    }

    public function eligibilityUpdate(Request $request, $id)
    {
        $request->validate([
            'career_eligible' => 'required',
            'rating' => 'required',
            'date_exam' => 'required',
            'place_exam' => 'required',
            'number' => 'required',
            'date_valid' => 'required',
            'attachment' => 'nullable|file',
        ]);

        $eligibility = Eligibility::findOrFail($id);

        $attachmentPath = $eligibility->attachment;
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment');
            $originalName = pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $attachment->getClientOriginalExtension();
            $randomNumber = rand(10000, 99999);
            $newFileName = $randomNumber . '_' . $originalName . '.' . $extension;
            $attachmentPath = $attachment->storeAs('Eligibility', $newFileName, 'public');
            
            if ($eligibility->attachment && \Storage::disk('public')->exists($eligibility->attachment)) {
                \Storage::disk('public')->delete($eligibility->attachment);
            }
        }

        $eligibility->update([
            'career_eligible' => $request->input('career_eligible'),
            'rating' => $request->input('rating'),
            'date_exam' => $request->input('date_exam'),
            'place_exam' => $request->input('place_exam'),
            'number' => $request->input('number'),
            'date_valid' => $request->input('date_valid'),
            'attachment' => $attachmentPath,
        ]);

        return redirect()->back()->with('success', 'Eligibility updated successfully.');
    }


    public function eliDelete($id)
    {
        $eligible = Eligibility::find($id);
        
        if ($eligible) {
            $filePath = public_path('storage/' . $eligible->attachment);
    
            if (file_exists($filePath)) {
                unlink($filePath);
            }
    
            $eligible->delete();
    
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

    public function eliApprove($id)
    {
        $eligible = Eligibility::find($id);
        
        if ($eligible) {
            $eligible->status = 1;
            $eligible->save();

            return response()->json([
                'status' => 200,
                'message' => "Approved Successfully",
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "Record not found",
            ]);
        }
    }

    
}
