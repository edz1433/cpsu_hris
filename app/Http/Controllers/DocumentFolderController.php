<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Models\DocuFolder;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Dpipop;
use Illuminate\Support\Facades\Route;

class DocumentFolderController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }
    
    public function createFolder(Request $request)
    {
        $request->validate([
            'folderName' => 'required|string|max:255',
            'office_access' => 'required|array',
        ]);
    
        $folderName = $request->input('folderName');
        $folderPath = public_path('Drives/' . $folderName);
    
        if (File::exists($folderPath)) {
            return redirect()->back()->with('error', 'Folder already exists.');
        }
    
        $newFolder = DocuFolder::create([
            'folder_name' => $folderName,
            'folder_category' => 'mainfolder',
            'folder_path' => 'Drives/' . $folderName,
            'office_access' => implode(', ', $request->input('office_access')),
        ]);
    
        File::makeDirectory($folderPath);
    
        return redirect()->back()->with('success', 'Folder created successfully.');
    }
    
    public function updateFolder(Request $request)
    {
        $request->validate([
            'fid' => 'required|string|max:255',
            'folderName' => 'required|string|max:255',
            'office_access' => 'required|array', 
        ]);
    
        $folderId = $request->input('fid');
        $newFolderName = $request->input('folderName');
        $newFolderPath = public_path('Drives/' . $newFolderName);
    
        $existingFolder = DocuFolder::find($folderId);
    
        if (!$existingFolder) {
            return redirect()->back()->with('error', 'Folder not found.');
        }
    
        $existingFolderPath = public_path($existingFolder->folder_path);
        $existingFolderName = basename($existingFolderPath);
    
        if ($newFolderName !== $existingFolderName) {
            if (File::exists($newFolderPath)) {
                return redirect()->back()->with('error', 'Folder with the new name already exists.');
            }
    
            $existingFolder->update([
                'folder_name' => $newFolderName,
                'folder_path' => 'Drives/' . $newFolderName,
                'office_access' => implode(', ', $request->input('office_access')), 
            ]);
    
            try {
                File::move($existingFolderPath, $newFolderPath);
                return redirect()->back()->with('success', 'Folder updated successfully.');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Error renaming folder: ' . $e->getMessage());
            }
        } else {
            if ($existingFolder->office_access !== implode(', ', $request->input('office_access'))) {
                $existingFolder->update([
                    'office_access' => implode(', ', $request->input('office_access')),
                ]);
                return redirect()->back()->with('success', 'Folder updated successfully.');
            }
            return redirect()->back()->with('success', 'Folder name is the same, no changes made.');
        }
    }

    public function subfolder($id)
    {
        $guard = $this->getGuard();

        $dpipops = Dpipop::join('employees', 'dpipops.user_id', '=', 'employees.id')
        ->where('folder_id', $id)
        ->select('dpipops.user_id', 'dpipops.mfo', 'dpipops.percent', 'employees.fname', 'employees.lname', 'employees.mname', 'employees.profile', 'employees.id as empid')
        ->get()
        ->groupBy('user_id');

        $topUser = Dpipop::select('user_id')
        ->selectRaw('COUNT(*) as count')
        ->groupBy('user_id')
        ->orderBy('count', 'desc')
        ->first();

        if ($topUser) {
            $userId = $topUser->user_id;
            $rowCount = Dpipop::where('user_id', $userId)->count();
        }

        $folder = DocuFolder::find($id);
        $connFolder = explode(',', $folder->connected_folder);
        $connFolders = DocuFolder::whereIn('id', $connFolder)->get();
        $offices = Office::all();
        $office = null;
        $subfolder = DocuFolder::where('folder_category', 'subfolder')->where('connected_folder', $id)->get();
        if ($folder->folder_category !== "mainfolder") {
            $subfolder = DocuFolder::where('folder_category', 'subfolder')
            ->whereRaw("SUBSTRING_INDEX(connected_folder, ',', -1) = ?", [$id])
            ->get();
        }

        if (!$folder) {
            return abort(404);
        }

        $folderPath = public_path($folder->folder_path);
        if(\Auth::guard('web')->check()){
            $uid = auth()->guard('web')->user()->id;
            $documents = Document::leftjoin('cpsupms.employees', 'documents.user_id', '=', 'cpsupms.employees.id')
            ->where('folder_id', $id)->get();
        }elseif(\Auth::guard('employee')->check()){
            
            $uid = auth()->guard('employee')->user()->id;
            $office = Office::where('office_head_id', $uid)->first();
    
            if (!empty($office)) {
                $offid = $office->id;
                $documents = Document::join('cpsupms.employees', function ($join) use ($offid, $uid) {
                    $join->on('documents.user_id', '=', 'cpsupms.employees.id')
                        ->where(function ($query) use ($offid, $uid) {
                            $query->where('cpsupms.employees.emp_dept', '=', $offid)
                                ->orWhere('documents.user_id', '=', $uid);
                        });
                })
                ->where('documents.folder_id', $id)
                ->get();

            } else{
                $documents = Document::where('folder_id', $id)->where('user_id', $uid)->get();
            }  
        }
        
        return view('drive.viewSubFolder', compact('folder', 'subfolder', 'id', 'connFolders', 'documents', 'dpipops', 'rowCount', 'guard', 'uid', 'folderPath', 'office', 'offices'));
    }
    
    public function createSubFolder(Request $request, $id)
    {
        $folder = DocuFolder::find($id);
        $request->validate([
            'folderName' => 'required|string|max:255',
            'office_access' => 'required|array',
        ]);
    
        $folderName = $request->input('folderName');
        $folderPath = public_path($folder->folder_path . '/' . $folderName);
    
        if (File::exists($folderPath)) {
            return redirect()->back()->with('error', 'Folder already exists.');
        }
    
        $newFolder = DocuFolder::create([
            'folder_name' => $folderName,
            'folder_category' => 'subfolder',
            'connected_folder' => empty($folder->connected_folder) ? $folder->id : $folder->connected_folder . ',' . $folder->id,
            'folder_path' => $folder->folder_path . '/' . $folderName,
            'office_access' => implode(', ', $request->input('office_access')),
        ]);
    
        File::makeDirectory($folderPath);
    
        return redirect()->back()->with('success', 'Folder created successfully.');
    }

    public function deleteFolder($id)
    {
        $folder = DocuFolder::find($id);
    
        if (!$folder) {
            return response()->json(['error' => 'Folder not found'], 404);
        }
    
        $folderPath = public_path($folder->folder_path);
    
        if (File::exists($folderPath)) {
            File::deleteDirectory($folderPath);
        }
    
        $folder->delete();
    
        return response()->json(['success' => 'Folder deleted successfully']);
    }
    
}
