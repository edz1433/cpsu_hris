<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Office;
use App\Models\SpmsPersonnel;
use App\Models\PrSetting;

class SpmsPersonnelController extends Controller
{
    public function spmsPersonnlist($cat)
    {
        $employees = Employee::all();
        $stratfunctions = PrSetting::all();

        $officecolleges = Office::leftJoin('dbcpsuhris.employees', 'offices.office_head_id', '=', 'dbcpsuhris.employees.id')
            ->get(['offices.*', 'dbcpsuhris.employees.fname as efname', 'dbcpsuhris.employees.lname as elname']);

        $personnelsQuery = SpmsPersonnel::leftJoin('employees', 'spms_personnels.empid', '=', 'employees.id')
            ->leftJoin('pr_settings', 'pr_settings.id', '=', 'employees.strat_function') // Now via Employee
            ->select(
                'spms_personnels.*',
                'spms_personnels.id as personid',
                'employees.fname',
                'employees.lname',
                'employees.strat_function as employee_strat_function',
                'pr_settings.category as strat_category'
            );

        if ($cat === 'pmt') {
            $personnelsQuery->where('spms_personnels.category', 1);
        } else {
            $personnelsQuery->where('spms_personnels.category', '!=', 1);
        }

        $personnels = $personnelsQuery->get();
        // Count employees with and without strat_function
        $stratFunctionHasCount = Employee::whereNotNull('strat_function')->count();
        $stratFunctionNoneCount = Employee::whereNull('strat_function')->count();

        return view('drive.personnel-list', compact('employees', 'personnels', 'officecolleges', 'stratfunctions', 'cat', 'stratFunctionHasCount', 'stratFunctionNoneCount'));
    }

    public function spmsPersonnEdit($cat, $id)
    {
        $employees = Employee::all();
        $stratfunctions = PrSetting::all();

        $officecolleges = Office::leftJoin('dbcpsuhris.employees', 'offices.office_head_id', '=', 'dbcpsuhris.employees.id')
            ->get(['offices.*', 'dbcpsuhris.employees.fname as efname', 'dbcpsuhris.employees.lname as elname']);

        $personnelsQuery = SpmsPersonnel::leftJoin('employees', 'spms_personnels.empid', '=', 'employees.id')
            ->leftJoin('pr_settings', 'pr_settings.id', '=', 'employees.strat_function')
            ->select(
                'spms_personnels.*',
                'spms_personnels.id as personid',
                'employees.fname',
                'employees.lname',
                'employees.strat_function as employee_strat_function',
                'pr_settings.category as strat_category'
            );

        if ($cat === 'pmt') {
            $personnelsQuery->where('spms_personnels.category', 1);
        } else {
            $personnelsQuery->where('spms_personnels.category', '!=', 1);
        }

        $personnels = $personnelsQuery->get();

        $personnelsEdit = SpmsPersonnel::join('employees', 'spms_personnels.empid', '=', 'employees.id')
            ->select(
                'spms_personnels.*',
                'employees.fname',
                'employees.lname',
                'employees.strat_function as employee_strat_function'
            )
            ->where('spms_personnels.id', $id)
            ->first();

            // Count employees with and without strat_function
            $stratFunctionHasCount = Employee::whereNotNull('strat_function')->count();
            $stratFunctionNoneCount = Employee::whereNull('strat_function')->count();

        return view('drive.personnel-list', compact('employees', 'personnels', 'personnelsEdit', 'officecolleges', 'stratfunctions', 'cat', 'stratFunctionHasCount', 'stratFunctionNoneCount'));
    }

    public function spmsPersonnCreate(Request $request)
    {
        $validated = $request->validate([
            'empid' => 'required|integer',
            'cat' => 'required|in:pmt,personnel',
        ]);

        $empid = $validated['empid'];
        $cat = $validated['cat'];

        if ($cat === 'pmt') {
            $request->validate([
                'category' => 'required|in:1',
                'position' => 'required|in:1,2,3',
            ]);

            $category = $request->input('category');

            $existing = SpmsPersonnel::where('empid', $empid)
                ->where('category', $category)
                ->first();

            if ($existing) {
                return redirect()->back()->withErrors(['category' => 'PMT record already exists for this employee.']);
            }

            SpmsPersonnel::create([
                'empid' => $empid,
                'category' => $category,
                'position' => $request->position,
            ]);

        } elseif ($cat === 'personnel') {
            $request->validate([
                'category' => 'required|integer',
                'off_coll_id' => 'required|in:1,2,3',
                'designation' => 'nullable|string|max:255',
                'strat_function' => 'required|string|max:255',
            ]);

            $category = $request->input('category');
            $offCollId = $request->input('off_coll_id');
            $designation = $request->input('designation');

            $existing = SpmsPersonnel::where('empid', $empid)
                ->where('category', $category)
                ->where('off_coll_id', $offCollId)
                ->first();

            if ($existing) {
                return redirect()->back()->withErrors(['category' => 'Personnel record already exists for this employee in the selected category and office/college.']);
            }

            SpmsPersonnel::create([
                'empid' => $empid,
                'category' => $category,
                'off_coll_id' => $offCollId,
                'designation' => $designation,
                'strat_function' => $request->strat_function,
            ]);

            Employee::where('id', $empid)->update([
                'strat_function' => $request->strat_function,
            ]);

            if ($category == 4) {
                Office::where('id', $offCollId)->update([
                    'office_head_id' => $empid,
                ]);
            }
        }

        return redirect()->route('spmsPersonnlist', ['cat' => $cat])
            ->with('success', 'Personnel added successfully.');
    }

    public function spmsPersonnUpdate(Request $request)
    {
        $validated = $request->validate([
            'empid' => 'required|integer|exists:employees,id',
            'cat' => 'required|in:pmt,personnel',
        ]);

        $empid = $validated['empid'];
        $cat = $validated['cat'];

        if ($cat === 'pmt') {
            $request->validate([
                'person_id' => 'required|exists:spms_personnels,id',
                'category' => 'required|in:1',
                'position' => 'required|in:1,2,3',
            ]);

            $personid = $request->input('person_id');
            $category = $request->input('category');

            // Check for existing PMT record
            $existing = SpmsPersonnel::where('empid', $empid)
                ->where('category', $category)
                ->where('id', '!=', $personid)
                ->first();

            if ($existing) {
                return redirect()->back()->withErrors(['category' => 'PMT record already exists for this employee.']);
            }

            $spmsPersonnel = SpmsPersonnel::findOrFail($personid);
            $spmsPersonnel->update([
                'empid' => $empid,
                'category' => $category,
                'position' => $request->position,
                'off_coll_id' => null,
                'designation' => null,
                'strat_function' => null,
            ]);

        } elseif ($cat === 'personnel') {
            $request->validate([
                'person_id' => 'required|exists:spms_personnels,id',
                'category' => 'required|in:2,3,4',
                'off_coll_id' => 'required',
                'designation' => 'nullable|string|max:255',
                'strat_function' => 'required|string|max:255',
            ]);
            
            $personid = $request->input('person_id');
            $category = $request->input('category');
            $offCollId = $request->input('off_coll_id');
            $designation = $request->input('designation');
            $stratFunction = $request->input('strat_function');

            // Check for duplicate personnel
            $existing = SpmsPersonnel::where('empid', $empid)
                ->where('category', $category)
                ->where('off_coll_id', $offCollId)
                ->where('id', '!=', $personid)
                ->first();

            if ($existing) {
                return redirect()->back()->withErrors([
                    'category' => 'Personnel record already exists for this employee in the selected category and office/college.',
                ]);
            }

            $spmsPersonnel = SpmsPersonnel::findOrFail($personid);
            $spmsPersonnel->update([
                'empid' => $empid,
                'category' => $category,
                'off_coll_id' => $offCollId,
                'designation' => $designation,
                'strat_function' => $stratFunction,
                'position' => null,
            ]);

            // Update Employee's strategic function
            Employee::where('id', $empid)->update([
                'strat_function' => $stratFunction,
            ]);

            // Update office head if category is 4
            if ($category == 4) {
                Office::where('id', $offCollId)->update([
                    'office_head_id' => $empid,
                ]);
            }
        }

        return redirect()->route('spmsPersonnlist', ['cat' => $cat])
            ->with('success', 'Personnel updated successfully.');
    }

    public function spmsPersonnDelete(Request $request) {
        $pmt = SpmsPersonnel::find($request->id);
    
        if (!$pmt) {
            return response()->json([
                'status' => 404,
                'message' => 'PMT not found',
            ]);
        }
    
        $pmt->delete();
    
        return response()->json([
            'status' => 200,
            'id' => $pmt->id,
        ]);
    }
}
