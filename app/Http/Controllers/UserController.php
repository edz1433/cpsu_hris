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

        $user = User::join('cpsupms.campuses', 'users.campus_id', '=', 'campuses.id')
            ->select('users.id as uid', 'users.*', 'campuses.*')
            ->get();
    
        return view("users.ulist", compact('user', 'camp', 'guard'));
    }

    public function uCreate(Request $request){
        $validator = Validator::make($request->all(), [
            'CampusName'=>'required',
            'FirstName'=>'required',
            'MiddleName'=>'required',
            'LastName'=>'required',
            'Username'=>'required|unique:users',
            'Password'=>'required',
            'Role'=>'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        else{
            $password = Hash::make($request->input('Password'));
            //Insert data into database
            $query = User::insert([
                'campus_id'=>$request->input('CampusName'),
                'fname'=>$request->input('FirstName'),
                'mname'=>$request->input('MiddleName'),
                'lname'=>$request->input('LastName'),
                'username'=>$request->input('Username'),
                'password'=>$password,
                'role'=>$request->input('Role'),
            ]);

            return redirect()->back()->with('success', 'User Added Successfully'); 
        }
    }

    public function uEdit($id){
        $guard = $this->getGuaard();
        $camp = Campus::all();
        $user = User::join('campuses', 'users.campus_id', '=', 'campuses.id')
        ->select('users.id as uid', 'users.*', 'campuses.*')
        ->get();
        $uEdit = User::join('campuses', 'users.campus_id', '=', 'campuses.id')
        ->select('users.id as uid', 'users.*', 'campuses.*')
        ->where('users.id', $id) 
        ->first();

        return view("users.ulist", compact('uEdit', 'user', 'camp', 'guard'));
    }

    public function uUpdate(Request $request)
    {
        $id = $request->input('uid');
        $validator = Validator::make($request->all(), [
            'CampusName' => 'required',
            'FirstName' => 'required',
            'MiddleName' => 'required',
            'LastName' => 'required',
            'Username' => 'required|unique:users,username,' . $id,
            'Role' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        } else {
            $user = User::find($id);
            if (!$user) {
                return redirect()->back()->with('error', 'User not found');
            }

            $user->campus_id = $request->input('CampusName');
            $user->fname = $request->input('FirstName');
            $user->mname = $request->input('MiddleName');
            $user->lname = $request->input('LastName');
            $user->username = $request->input('Username');
            $user->role = $request->input('Role');
            
            if ($request->has('Password')) {
                $user->password = Hash::make($request->input('Password'));
            }

            $user->save();

            return redirect()->back()->with('success', 'User Updated Successfully');
        }
    }

    public function uDelete($id){
        $users = User::find($id);
        $users->delete();

        return response()->json([
            'status'=>200,
            'uid'=>$id,
        ]);
    }

}
