<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
use Illuminate\Support\Facades\Hash;

class PdsController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function empPDS(){
        $guard = $this->getGuard();
        $empid = auth()->guard($guard)->user()->id; 
        $employee = Employee::find($empid);
        
        $hprovinces = Province::where('region_id', $employee->add_region)->get();
        $hcities = City::where('city_id', $employee->add_city)->get();
        $hbarangays = Barangay::find($employee->add_brgy);

        $gprovinces = Province::where('region_id', $employee->padd_region)->get();
        $gcities = City::where('city_id', $employee->padd_city)->get();
        $gbarangays = Barangay::find($employee->padd_brgy);
        
        $regions = Region::all();
        $offices = Office::where('office_name', 'not like', '%UNKNOWN%')
                 ->where('office_name', 'not like', '%CAMPUS%')
                 ->get();

        $stat = Status::where('status_name', '!=', 'Part-time/JO')->get();
        $quali = Qualification::all();
        $camp = Campus::where('id', auth()->guard($guard)->user()->camp_id)->get();

        return view("emp.pds", compact('employee', 'guard', 'camp', 'offices', 'stat', 'quali', 'regions', 'hprovinces', 'hcities', 'hbarangays', 'gprovinces', 'gcities', 'gbarangays', 'empid'));
    }
}
