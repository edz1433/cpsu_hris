<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Office;
use App\Models\SpmsPersonnel;
use App\Models\PrSetting;

class SpmsPersonnelController extends Controller
{
    public function spmsPersonnlist($cat){
        $employees = Employee::all();
        $stratfunctions = PrSetting::all();
        $officecolleges = Office::leftJoin('dbcpsuhris.employees', 'offices.office_head_id', '=', 'dbcpsuhris.employees.id')
        ->get(['offices.*', 'dbcpsuhris.employees.fname as efname', 'dbcpsuhris.employees.lname as elname']); 

        $personnelsQuery = SpmsPersonnel::leftJoin('employees', 'spms_personnels.empid', '=', 'employees.id')
            ->leftJoin('pr_settings', 'pr_settings.id', '=', 'spms_personnels.strat_function')
            ->select(
                'spms_personnels.*',
                'spms_personnels.id as personid',
                'employees.fname',
                'employees.lname',
                'pr_settings.category as strat_category'
            );

        if ($cat == 'pmt') {
            $personnelsQuery->where('spms_personnels.category', 1);
        } else {
            $personnelsQuery->where('spms_personnels.category', '!=', 1);
        }

        $personnels = $personnelsQuery->get();
               
        return view('drive.personnel-list', compact('employees', 'personnels', 'officecolleges', 'stratfunctions', 'cat'));
    }

    public function spmsPersonnEdit($cat, $id){
        $employees = Employee::all();
        $stratfunctions = PrSetting::all();
        $officecolleges = Office::leftJoin('dbcpsuhris.employees', 'offices.office_head_id', '=', 'dbcpsuhris.employees.id')
        ->get(['offices.*', 'dbcpsuhris.employees.fname as efname', 'dbcpsuhris.employees.lname as elname']); 
        
        $personnelsQuery = SpmsPersonnel::leftJoin('employees', 'spms_personnels.empid', '=', 'employees.id')
            ->leftJoin('pr_settings', 'pr_settings.id', '=', 'spms_personnels.strat_function')
            ->select(
                'spms_personnels.*',
                'spms_personnels.id as personid',
                'employees.fname',
                'employees.lname',
                'pr_settings.category as strat_category'
            );

        if ($cat == 'pmt') {
            $personnelsQuery->where('spms_personnels.category', 1);
        } else {
            $personnelsQuery->where('spms_personnels.category', '!=', 1);
        }

        $personnels = $personnelsQuery->get();

        $personnelsEdit = SpmsPersonnel::join('employees', 'spms_personnels.empid', '=', 'employees.id')
              ->select('spms_personnels.*', 'employees.fname', 'employees.lname')
              ->where('spms_personnels.id', $id)
              ->first();

        return view('drive.personnel-list', compact('employees', 'personnels', 'personnelsEdit', 'officecolleges', 'stratfunctions', 'cat'));
    }

    public function spmsPersonnCreate(Request $request)
    {
        $validated = $request->validate([
            'empid' => 'required|integer',
            'cat' => 'required',
        ]);

        $empid = $validated['empid'];
        $cat = $validated['cat'];

        if ($cat === 'pmt') {
            $request->validate([
                'category' => 'required|in:1',
                'position' => 'required|in:1,2,3',
            ]);

            $existing = SpmsPersonnel::where('empid', $empid)
                ->where('category', 1)
                ->first();

            if ($existing) {
                return redirect()->back()->withErrors(['category' => 'PMT record already exists for this employee.']);
            }

            SpmsPersonnel::create([
                'empid' => $empid,
                'category' => 1,
                'position' => $request->position,
            ]);

        } elseif ($cat === 'personnel') {
            $request->validate([
                'category' => 'required|array|min:1|max:2',
                'category.*' => 'in:2,3,4',
                'off_coll_id' => 'required|array|min:1|max:2',
                'designation' => 'nullable|string|max:255',
                'strat_function' => 'nullable|string|max:255',
            ]);

            $categories = $request->category;
            $offices = $request->off_coll_id;

            // Check if category and office count matches
            if (count($categories) !== count($offices)) {
                return redirect()->back()->withErrors([
                    'off_coll_id' => 'Each selected category must have a corresponding office/college.',
                ]);
            }

            // Disallow selecting both 2 and 3
            if (in_array(2, $categories) && in_array(3, $categories)) {
                return redirect()->back()->withErrors([
                    'category' => 'You cannot select both DEAN (2) and CAMPUS ADMINISTRATOR (3) at the same time.',
                ]);
            }

            // ✅ PRE-CHECK: If any selected office already has a head (when category is 4)
            foreach ($categories as $index => $category) {
                if ($category == 4) {
                    $offCollId = $offices[$index];
                    $office = SpmsPersonnel::where('off_coll_id', $offCollId)->first();

                    if ($office && !is_null($office->office_head_id) && $office->office_head_id != $empid) {
                        return redirect()->back()->withErrors([
                            'off_coll_id' => 'Office/College already has an assigned office head.',
                        ]);
                    }
                }
            }

            // ✅ Insert after passing validation
            foreach ($categories as $index => $category) {
                $offCollId = $offices[$index];

                $existing = SpmsPersonnel::where('empid', $empid)
                    ->where('category', $category)
                    ->where('off_coll_id', $offCollId)
                    ->first();

                if ($existing) {
                    continue;
                }

                if ($category == 2) {
                    $designation = 'DEAN';
                } elseif ($category == 3) {
                    $designation = 'CAMPUS ADMINISTRATOR';
                } else {
                    $designation = $request->designation;
                }

                SpmsPersonnel::create([
                    'empid' => $empid,
                    'category' => $category,
                    'off_coll_id' => $offCollId,
                    'designation' => $designation,
                    'strat_function' => $request->strat_function,
                ]);

                if ($category == 4 && $offCollId) {
                    Office::where('id', $offCollId)->update([
                        'office_head_id' => $empid,
                    ]);
                }
            }
        }

        return redirect()->route('spmsPersonnlist', ['cat' => $cat])
            ->with('success', 'Personnel added successfully.');
    }


    public function spmsPersonnUpdate(Request $request)
    {
        $validated = $request->validate([
            'empid' => 'required|integer|exists:employees,id',
            'cat' => 'required',
        ]);

        $empid = $validated['empid'];
        $cat = $validated['cat'];
        $messages = [];

        if ($cat === 'pmt') {
            $request->validate([
                'person_id' => 'required|exists:spms_personnels,id',
                'category' => 'required|in:1',
                'position' => 'required|in:1,2,3',
            ]);

            $personid = $request->person_id;

            $existing = SpmsPersonnel::where('empid', $empid)
                ->where('category', 1)
                ->where('id', '!=', $personid)
                ->first();

            if ($existing) {
                return redirect()->back()->withErrors(['category' => 'PMT record already exists for this employee.']);
            }

            $spmsPersonnel = SpmsPersonnel::findOrFail($personid);
            $spmsPersonnel->update([
                'empid' => $empid,
                'category' => 1,
                'position' => $request->position,
                'off_coll_id' => null,
                'designation' => null,
                'strat_function' => null,
            ]);

        } elseif ($cat === 'personnel') {
            $request->validate([
                'person_id' => 'nullable|exists:spms_personnels,id',
                'category' => 'required|in:2,3,4',
                'off_coll_id' => 'required',
                'designation' => 'nullable|string|max:255',
                'strat_function' => 'nullable|string|max:255',
            ]);

            $personid = $request->person_id;
            $category = $request->category;
            $offCollId = $request->off_coll_id;

            // Check for duplicate personnel
            $existing = SpmsPersonnel::where('empid', $empid)
                ->where('category', $category)
                ->where('off_coll_id', $offCollId)
                ->when($personid, function($query) use ($personid) {
                    $query->where('id', '!=', $personid);
                })
                ->first();  

            if (!$existing) {
                $spmsPersonnel = SpmsPersonnel::find($personid);
                if ($spmsPersonnel) {
                    $spmsPersonnel->update([
                        'empid' => $empid,
                        'category' => $category,
                        'off_coll_id' => $offCollId,
                        'position' => null,
                    ]);
                }
            }

            // Update designation and strategic function
            SpmsPersonnel::where('empid', $empid)->update([
                'designation' => $request->designation,
                'strat_function' => $request->strat_function,
            ]);
        }

        // Final redirect with success and any warnings
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