<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\OpcrMfo;
use App\Models\Pmt;

class PmtController extends Controller
{
    public function pmtlist(){
        $employees = Employee::all();
        $pmts = Pmt::leftjoin('employees', 'pmts.empid', '=', 'employees.id')
               ->select('pmts.*', 'pmts.id as pmtid', 'employees.fname', 'employees.lname')
               ->get();
        return view('drive.pmt-list', compact('employees', 'pmts'));
    }

    public function pmtEdit($id){
        $employees = Employee::all();
        $pmts = Pmt::join('employees', 'pmts.empid', '=', 'employees.id')
               ->select('pmts.*', 'pmts.id as pmtid', 'employees.fname', 'employees.lname')
               ->get();

        $pmtEdit = Pmt::join('employees', 'pmts.empid', '=', 'employees.id')
                  ->select('pmts.*', 'employees.fname', 'employees.lname')
                  ->where('pmts.id', $id)
                  ->first();

        return view('drive.pmt-list', compact('employees', 'pmts', 'pmtEdit'));
    }

    public function pmtCreate(Request $request)
    {
        $validated = $request->validate([
            'empid' => 'required|exists:employees,id',
            'position' => 'required|in:1,2,3',
        ]);
    
        Pmt::create([
            'empid' => $validated['empid'],
            'position' => $validated['position'],
        ]);
    
        return redirect()->back()->with('success', 'PMT member added successfully.');
    }

    public function pmtUpdate(Request $request)
    {
        $validated = $request->validate([
            'pmt_id' => 'required|exists:pmts,id',
            'empid' => 'required|exists:employees,id',
            'position' => 'required|in:1,2,3',
        ]);
    
        $pmt = Pmt::findOrFail($validated['pmt_id']);
        $pmt->update([
            'empid' => $validated['empid'],
            'position' => $validated['position'],
        ]);
    
        return redirect()->back()->with('success', 'PMT member updated successfully.');
    }

    public function pmtDelete(Request $request) {
        $pmt = Pmt::find($request->id);
    
        if (!$pmt) {
            return response()->json([
                'status' => 404,
                'message' => 'pmt not found',
            ]);
        }
    
        $pmt->delete();
    
        return response()->json([
            'status' => 200,
            'id' => $pmt->id,
        ]);
    }
}
