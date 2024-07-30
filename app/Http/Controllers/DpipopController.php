<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dpipop;

class DpipopController extends Controller
{
    public function getFormData(Request $request)
    {
        $formdata = Dpipop::where('user_id', $request->user_id)->where('folder_id', $request->folder_id)->get();
        
        return response()->json(['formdata' => $formdata]);
    }
    
    public function createpr(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'folder_id' => 'required|exists:docu_folders,id',
            'mfo.*' => 'required|string',
            'percent.*' => 'required|integer|min:0|max:100',
        ]);

        $userId = $request->input('user_id');
        $folderId = $request->input('folder_id');

        $data = [];
        foreach ($request->input('mfo') as $index => $mfo) {
            $data[] = [
                'user_id' => $userId,
                'folder_id' => $folderId,
                'mfo' => $mfo,
                'percent' => $request->input('percent')[$index],
            ];
        }

        Dpipop::insert($data);

        return redirect()->back()->with('success', 'Data saved successfully!');
    }
}
