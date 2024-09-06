<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Region;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use App\Models\Employee;
use App\Models\Status;
use App\Models\Campus;
use App\Models\Office;
use App\Models\Qualification;
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
use App\Models\PayrollEmployee;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
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

    public function emp_list()
    {
        $guard = $this->getGuard();
        $user = User::where('username', auth()->user()->username)->first();
        $offices = Office::where('office_name', 'not like', '%UNKNOWN%')
                 ->where('office_name', 'not like', '%CAMPUS%')
                 ->get();

        $stat = Status::where('status_name', '!=', 'Part-time/JO')->get();

        if (auth()->user()->role == "Payroll Extension") {
            $stat->whereNotIn('status_name', ['Regular', 'Part-time/JO'])->get();
        }
        
        $employee = Employee::join('dbcpsupms.offices', 'employees.emp_dept', '=', 'dbcpsupms.offices.id')
        ->join('dbcpsupms.statuses', 'employees.emp_status', '=', 'dbcpsupms.statuses.id')
        ->join('campuses', 'employees.camp_id', '=', 'campuses.id')
        ->select(
            DB::raw('ROW_NUMBER() OVER (ORDER BY employees.id) as ids'),
            DB::raw("CONCAT(employees.lname, ', ', employees.fname, ' ', employees.mname) AS full_name"),
            'employees.*',
            'offices.office_name',
            'statuses.status_name',
            'campuses.campus_abbr',
        );
        
        if (auth()->user()->role != "Administrator" && auth()->user()->role != "Payroll Administrator") {
            $employee->where('employees.camp_id', '=', auth()->user()->campus_id);
        }
    
        $employee = $employee->get();
        $quali = Qualification::all();
        $camp = (auth()->user()->campus_id == 1) ? Campus::all() : Campus::where('id', auth()->user()->campus_id)->get();
    
        return view("emp.emplist", compact('employee', 'offices', 'stat', 'quali', 'camp', 'guard'));
    }

    public function empAdd(){
        $regions = Region::all();
        $guard = $this->getGuard();
        $offices = Office::where('office_name', 'not like', '%UNKNOWN%')
                 ->where('office_name', 'not like', '%CAMPUS%')
                 ->get();
         
        $supervisor = Employee::where("emp_status", 1)->get();

        $stat = Status::where('status_name', '!=', 'Part-time/JO')->get();
        $quali = Qualification::all();
        $camp = (auth()->user()->campus_id == 1) ? Campus::all() : Campus::where('id', auth()->user()->campus_id)->get();
        
        return view("emp.empadd", compact('guard', 'camp', 'offices', 'stat', 'quali', 'regions', 'supervisor'));
    }

    public function empCreate(Request $request)
    {
        $validated = $request->validate([
            'lname' => 'required|string',
            'fname' => 'required|string',
            'mname' => 'required|string',
        ]);

        $existingEmployee = Employee::where('lname', $request->lname)
                ->where('fname', $request->fname)
                ->where('mname', $request->mname)
                ->first();

        if ($existingEmployee) {
            return redirect()->back()->withErrors(['Employee already exists.']);
        }


        if ($request->filled('ProfileImage')) {
            $base64Image = $request->input('ProfileImage');
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
            $fileName = date('Ymdhis');
            $fileExtension = '.jpg';
            $fullFileName = $fileName . $fileExtension;
            $file = public_path('Profile/Employee/' . $fullFileName);
            file_put_contents($file, $imageData);
        } elseif ($request->hasFile('ProfileImage1')) {
            $profileImage1 = $request->file('ProfileImage1');
            $fileName = date('Ymdhis') . '.' . $profileImage1->getClientOriginalExtension();
            $profileImage1->move(public_path('Profile/Employee/'), $fileName);
            $fullFileName = $fileName;
        } 
        else {
            if ($request->sex == 'Male') {
                $fullFileName = 'default-male.png';
            } elseif ($request->sex == 'Female') {
                $fullFileName = 'default-female.png';
            } else {
                $fullFileName = 'default.png';
            }
        }        

        $lastEmployee = Employee::orderBy('emp_ID', 'desc')->first();

        if ($lastEmployee) {
            $lastEmpID = $lastEmployee->emp_ID;
            $lastNumericPart = (int)substr($lastEmpID, 3);
            $newNumericPart = $lastNumericPart + 1;
            $newEmpID = 'EMP' . str_pad($newNumericPart, 4, '0', STR_PAD_LEFT);
        } else {
            $newEmpID = 'EMP0001';
            
        }
        $password = substr($newEmpID, 0, 3).substr($newEmpID, 3);

        $employee = new Employee([
            'profile' => $fullFileName,
            'date_hired' => $request->date_hired,
            'item_plan' => $request->item_plan,
            'lname' => $request->lname,
            'fname' => $request->fname,
            'mname' => $request->mname,
            'suffix' => $request->suffix,
            'title_suffix' => $request->title_suffix,
            'position' => $request->position,
            'emp_ID' => $newEmpID,
            'emp_status' => $request->emp_status,
            'emp_dept' => $request->emp_dept,
            'em_supervise' => $request->em_supervise,
            'item_no' => $request->item_no,
            'prefix' => $request->prefix,
            'bdate' => $request->bdate,
            'age' => $request->age,
            'b_place' => $request->b_place,
            'sex' => $request->sex,
            'civil_status' => $request->civil_status,
            'camp_id' => $request->camp_id,
            'height_cm' => $request->height_cm,
            'height_ft' => $request->height_ft,
            'weight_kg' => $request->weight_kg,
            'weight_lb' => $request->weight_lb,
            'b_type' => $request->b_type,
            'gsis' => $request->gsis,
            'pagibig' => $request->pagibig,
            'philhealth' => $request->philhealth,
            'sss' => $request->sss,
            'tin' => $request->tin,
            'citizenship' => $request->citizenship,
            'c_category' => $request->c_category,
            'country' => $request->country,
            'telephone' => $request->telephone,
            'org_email' => $request->org_email,
            'mobile' => $request->mobile,
            'add_block' => $request->add_block,
            'add_street' => $request->add_street,
            'add_village' => $request->add_village,
            'add_brgy' => $request->add_brgy,
            'add_city' => $request->add_city,
            'add_prov' => $request->add_prov,
            'add_region' => $request->add_region,
            'add_zcode' => $request->add_zcode,
            'padd_block' => $request->padd_block,
            'padd_street' => $request->padd_street,
            'padd_village' => $request->padd_village,
            'padd_brgy' => $request->padd_brgy,
            'padd_city' => $request->padd_city,
            'padd_prov' => $request->padd_prov,
            'padd_region' => $request->padd_region,
            'padd_zcode' => $request->padd_zcode,
            'special_pl' => ($request->emp_status == 1) ? 3 : 0,
            'solo_pl' => ($request->emp_status == 1) ? 7 : 0,
            'username' => $newEmpID,
            'password' => $password,
        ]);
        
        $employee->save();
        
        PayrollEmployee::create([
            'emp_ID' => $newEmpID,
            'lname' => $request->lname,
            'fname' => $request->fname,
            'mname' => $request->mname,
            'position' => $request->position,
            'emp_status' => $request->emp_status,
            'emp_dept' => $request->emp_dept,
            'camp_id' => $request->camp_id,
            'emp_salary' => 0.00,
        ]);

        $models = ['FamilyBg', 'EducBg', 'OtherInfo', 'InfoQuestion', 'PdsReference', 'GovId'];

        foreach ($models as $model) {
            $modelClass = "App\\Models\\{$model}";
        
            if (class_exists($modelClass)) {
                $modelClass::create([
                    'empid' => $newEmpID,
                ]);
            } else {
                throw new Exception("Model {$modelClass} not found.");
            }
        }
             
        
        return redirect()->back()->with('success', 'Employee added successfully.');
    }

    public function updateProfilePicture(Request $request, $id)
    {
        $request->validate([
            'profileImage' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
    
        $employee = Employee::find($id);
    
        if (!$employee) {
            return response()->json(['error' => 'Employee not found.'], 404);
        }
    
        if ($request->hasFile('profileImage')) {
            $profileImagePath = public_path('Profile/Employee/');

            if ($employee->profile && file_exists($profileImagePath . $employee->profile)) {
                unlink($profileImagePath . $employee->profile);
            }
    
            $profileImage1 = $request->file('profileImage');
            $fileName = date('Ymdhis') . '.' . $profileImage1->getClientOriginalExtension();
            $profileImage1->move($profileImagePath, $fileName);
    
            $employee->profile = $fileName;
            $employee->save();
        }
    
        return response()->json([
            'success' => 'Profile picture updated successfully.',
            'profile' => asset('Profile/Employee/' . $fileName),
        ]);
    }    

    public function employeeUpdate(Request $request){
        $employee = Employee::findOrFail($request->id);
        $column = $request->column;
        if ($column == 'bdate') {
            $bdate = Carbon::parse($request->value);
            $age = $bdate->age;
            $employee->update([
                $column => $request->value,
                'age' => $age
            ]);
        } 
        if ($column == 'citizenship' && $request->value == 1) {
            $employee->update([
                $column => $request->value,
                'c_category' => '',
                'country' => '',
            ]);
        }
        else {
            $employee->update([
                $column => $request->value
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function PDS($id){
        $guard = $this->getGuard();
        $empid = $id; 
        $employee = Employee::find($empid);
        $columnstatus = $this->columnStat($employee->emp_ID);

        $hprovinces = Province::where('region_id', $employee->add_region)->get();
        $hcities = City::where('city_id', $employee->add_city)->get();
        $hbarangays = Barangay::find($employee->add_brgy);

        $gprovinces = Province::where('region_id', $employee->padd_region)->get();
        $gcities = City::where('city_id', $employee->padd_city)->get();
        $gbarangays = Barangay::find($employee->padd_brgy);

        $supervisor = Employee::where('id', '!=', $empid)->where('emp_status', 1)->get();
        
        $regions = Region::all();
        $offices = Office::where('office_name', 'not like', '%UNKNOWN%')
                 ->where('office_name', 'not like', '%CAMPUS%')
                 ->get();

        $stat = Status::where('status_name', '!=', 'Part-time/JO')->get();
        $quali = Qualification::all();
        $camp = (auth()->user()->campus_id == 1) ? Campus::all() : Campus::where('id', auth()->user()->campus_id)->get();

        return view("emp.pds", compact('employee', 'supervisor', 'guard', 'camp', 'offices', 'stat', 'quali', 'regions', 'hprovinces', 'hcities', 'hbarangays', 'gprovinces', 'gcities', 'gbarangays', 'empid', 'columnstatus'));
    }

    public function empEdit($id)
    {
        $emp = Employee::find($id);
        return response()->json([
            'status'=>200,
            'emp'=>$emp,
        ]);
    }

    public function empDelete($id){
        $emp = Employee::find($id);
        $emp->delete();

        return response()->json([
            'status'=>200,
            'message'=>"Deleted Successfully",
        ]);
    }

    public function empPartimeRate(Request $request){
        $validator = Validator::make($request->all(), [
            'PartimeRate'=>'',
        ]);

        if($validator->fails()){
            return response()->json([
                'status'=>400,
                'error'=>$validator->messages(),
            ]);
        }

        else{
            $update = [
                'partime_rate'=>round($request->input('PartimeRate'), 2)
            ];
            DB::table('employees')->where('id', $request->empid)->update($update);

            return response()->json([
                'status'=>200,
                'message'=>"Successfully Update",
            ]);
        }
    }
    
    public function empEditRate($id){
        $emp = Employee::find($id);
        return response()->json([
            'status'=>200,
            'emp'=>$emp,
        ]);
    }

    public function toggleAcctStat(Request $request)
    {
        $employee = Employee::findOrFail($request->id);
        $employee->stat_1 = $request->stat_1;
        $employee->save();
        
        return response()->json(['success' => true, 'message' => 'User role updated successfully.']);
    }    

    public function updateEmployeePasswords()
    {
        try {
            $employees = Employee::all();
    
            foreach ($employees as $employee) {
                if (!empty($employee->username)) {
                    $username = $employee->username;
    
                    $hashedPassword = Hash::make($username);
    
                    $employee->password = $hashedPassword;
                    $employee->save();
                }
            }
    
            return 'Passwords updated successfully.';
    
        } catch (\Exception $e) {
            return 'An error occurred: ' . $e->getMessage();
        }
    }
    

}
