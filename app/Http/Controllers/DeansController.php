<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Dean;

class DeansController extends Controller
{
    public function deanlist(){
        $employees = Employee::all();
        $deans = Dean::leftjoin('employees', 'deans.empid', '=', 'employees.id')
               ->select('deans.*', 'deans.id as dean_id', 'employees.fname', 'employees.lname')
               ->get();
        return view('drive.deans-list', compact('employees', 'deans'));
    }

    public function deansCreate(Request $request)
    {
        $validated = $request->validate([
            'empid' => 'required|exists:employees,id',
            'designation' => 'required|string|max:255',
        ]);
    
        Dean::create([
            'empid' => $validated['empid'],
            'designation' => $validated['designation'],
            'position' => 1, // Assuming position 1 represents deans
        ]);
    
        return redirect()->back()->with('success', 'Dean added successfully.');
    }

    public function deansEdit($id){
        $employees = Employee::all();
        $deans = Dean::join('employees', 'deans.empid', '=', 'employees.id')
               ->where('deans.position', 1) // Assuming position 1 represents deans
               ->select('deans.*', 'deans.id as dean_id', 'employees.fname', 'employees.lname')
               ->get();

        $deanEdit = Dean::join('employees', 'deans.empid', '=', 'employees.id')
                  ->select('deans.*', 'employees.fname', 'employees.lname')
                  ->where('deans.id', $id)
                  ->where('deans.position', 1) // Ensure editing only deans
                  ->first();

        return view('drive.deans-list', compact('employees', 'deans', 'deanEdit'));
    }

    public function deansUpdate(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:deans,id',
            'empid' => 'required|exists:employees,id',
            'designation' => 'required|string|max:255',
        ]);
    
        $dean = Dean::findOrFail($validated['id']);
        $dean->update([
            'empid' => $validated['empid'],
            'designation' => $validated['designation'],
            'position' => 1, // Ensure position remains as dean
        ]);
    
        return redirect()->back()->with('success', 'Dean updated successfully.');
    }

    public function deansDelete(Request $request) {
        $dean = Dean::where('id', $request->id)->where('position', 1)->first();
    
        if (!$dean) {
            return response()->json([
                'status' => 404,
                'message' => 'Dean not found',
            ]);
        }
    
        $dean->delete();
    
        return response()->json([
            'status' => 200,
            'id' => $dean->id,
        ]);
    }
}
