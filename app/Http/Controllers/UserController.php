<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Campus;

class UserController extends Controller
{
    public function getGuaard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function ulist()
    {
        $guard = $this->getGuaard();
        $camp = Campus::on('payroll')->get();

        $users = User::join('dbcpsupms.campuses', 'users.campus_id', '=', 'campuses.id')
            ->select('users.id as uid', 'users.*', 'campuses.*')
            ->get();
    
        return view("users.user-list", compact('users', 'camp', 'guard'));
    }

    public function uCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lname' => 'required',
            'fname' => 'required',
            'mname' => 'required',
            'username' => 'required|unique:users',
            'password' => 'required',
            'role' => 'required',
            'gender' => 'required',
            'campus_id' => 'required',
            'access' => 'array',
            'access.*' => 'in:0,1',
        ]);
    
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    
        $password = Hash::make($request->input('password'));
    
        $userData = [
            'fname' => $request->input('fname'),
            'mname' => $request->input('mname'),
            'lname' => $request->input('lname'),
            'username' => $request->input('username'),
            'password' => $password,
            'campus_id' => $request->input('campus_id'),
            'role' => $request->input('role'),
            'gender' => $request->input('gender'),
        ];
    
        $accessPermissions = array_fill(0, 8, '0');
        foreach ($request->input('access', []) as $index => $value) {
            $accessPermissions[$index] = '1';
        }
    
        // if ($request->input('Role') === 'Administrator') {
        //     $accessPermissions[7] = '1';
        // }else{
        //     $accessPermissions[7] = '0';
        // }
        
        $userData['access'] = implode(',', $accessPermissions);
    
        User::create($userData);
    
        return redirect()->back()->with('success', 'User created successfully.');
    }
    
    public function uEdit($id)
    {
        $guard = $this->getGuaard();
        $users = User::select('id as uid', 'users.*')->get();
        $uEdit = User::find($id);
        $camp = Campus::on('payroll')->get();
    
        if (!$uEdit) {
            return redirect()->back()->with('error', 'User not found.');
        }
    
        return view("users.user-list", compact('users', 'uEdit', 'camp', 'guard'));
    }  

    public function uUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lname' => 'required',
            'fname' => 'required',
            'mname' => 'required',
            'username' => 'required',
            'password' => 'nullable',
            'role' => 'required',
            'gender' => 'required',
            'campus_id' => 'required',
            'access' => 'array',
            'access.*' => 'in:0,1',
        ]);
    
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    
        $user = User::find($request->input('uid'));
    
        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'User not found']);
        }
    
        $userData = [
            'fname' => $request->input('fname'),
            'mname' => $request->input('mname'),
            'lname' => $request->input('lname'),
            'username' => $request->input('username'),
            'campus_id' => $request->input('campus_id'),
            'role' => $request->input('role'),
            'gender' => $request->input('gender'),
        ];
    
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->input('password'));
        }
    
        $accessPermissions = array_fill(0, 9, '0');
    
        foreach ($request->input('access', []) as $index => $value) {
            $accessPermissions[$index] = '1';
        }
    
        $userData['access'] = implode(',', $accessPermissions);
    
        $user->update($userData);
    
        return redirect()->back()->with('success', 'User updated successfully.');
    }    

    public function uDelete($id) {
        $user = User::find($id);
    
        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found',
            ]);
        }
    
        $user->delete();
    
        return response()->json([
            'status' => 200,
            'id' => $id,
        ]);
    }

}
