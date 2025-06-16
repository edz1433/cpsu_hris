<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\DocuFolder; 
use App\Models\Document;
use App\Models\Dpipop;
use App\Models\PrData;
use App\Models\Opcr;
use App\Models\OpcrMfo;
use App\Models\OpcrMfoData;
use App\Models\Dpcr;
use App\Models\DpcrMfo;
use App\Models\DpcrMfoData;

class DocumentController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    function shortDecrypt($encrypted)
    {
        $key = 'fA7xB93kL0pTzWmQ';
        $cipher = 'AES-128-ECB';
        $encrypted = strtr($encrypted, '-_', '+/');
        return openssl_decrypt(base64_decode($encrypted), $cipher, $key, 0);
    }

    public function storeFile(Request $request, $id)
    {
        $process = isset($request->process) ? $request->process : '';

        $request->validate([
            'file' => 'required|mimes:pdf|max:3072',
        ]);
        
        if (\Auth::guard('web')->check()) {
            $user_id = auth()->guard('web')->user()->id;
        } elseif (\Auth::guard('employee')->check()) {
            $user_id = auth()->guard('employee')->user()->id;
        }
        $folder = DocuFolder::find($id);
    
        $file = $request->file('file');
        $originalFileName = $file->getClientOriginalName();
    
        do {
            $randomNumber = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    
            $fileName = $randomNumber . '_' . $originalFileName;
    
            $filePath = public_path($folder->folder_path . '/' . $fileName);
        } while (file_exists($filePath));
    
        $document = Document::create([
            'user_id' => $user_id,
            'folder_id' => $id,
            'file' => $fileName,
            'file_ext' => 'pdf',
        ]);
    
        $file->move(public_path($folder->folder_path), $fileName);
    
        if(!empty($process)){
            return redirect()->back()->with('success', 'File uploaded successfully.');
        }else{
            return response()->json(['success' => 'File uploaded successfully.']);
        }
    } 

    public function updateFile(Request $request)
    {
        $request->validate([
            'file_id' => 'required|string', 
            'file_name' => 'required|string', 
        ]);
        
        $fileId = $request->file_id;

        $document = Document::find($fileId);
    
        if (!$document) {
            return response()->json(['error' => 'File not found.'], 404);
        }
    
        $newFileName = $request->file_name;
    
        $folder = DocuFolder::find($document->folder_id);
        $newFilePath = public_path($folder->folder_path . '/' . $newFileName);
    
        do {
            $randomNumber = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $newFileName = $randomNumber . '_' . $newFileName.'.'.$document->file_ext;
            $newFilePath = public_path($folder->folder_path . '/' . $newFileName);
        } while (file_exists($newFilePath));
    
        $existingFilePath = public_path($folder->folder_path . '/' . $document->file);
        if (file_exists($existingFilePath)) {
            rename($existingFilePath, $newFilePath);
        }
    
        $document->update([
            'file' => $newFileName,
        ]);
    
        return redirect()->back()->with('success', 'File name updated successfully.');

    }

    public function perRating($cat, $empid, $prnumber){
        $guard = $this->getGuard();
        $dempid = $this->shortDecrypt($empid);
        $dprnumber = $this->shortDecrypt($prnumber);
        
        $dempid = ($empid) ? $dempid : auth()->guard($guard)->user()->id;
        $employees = Employee::where('emp_status', 1)->get();

        $prefix = substr($dprnumber, 0, 2); // e.g., O-, D-, I-
        $typeMap = [
            'O-' => ['pr_model' => Opcr::class, 'mfo_model' => OpcrMfo::class, 'data_model' => OpcrMfoData::class, 'key' => 'opcr_id'],
            'D-' => ['pr_model' => Dpcr::class, 'mfo_model' => DpcrMfo::class, 'data_model' => DpcrMfoData::class, 'key' => 'dpcr_id'],
            'I-' => ['pr_model' => Ipcr::class, 'mfo_model' => IpcrMfo::class, 'data_model' => IpcrMfoData::class, 'key' => 'ipcr_id'],
        ];

        if (!isset($typeMap[$prefix])) {
            return redirect()->back()->with('error', 'Invalid PR number format.');
        }

        $models = $typeMap[$prefix];
        $prs = $models['pr_model']::where('user_id', $dempid)->where('pr_number', $dprnumber)->get();

        $cores = $prs->get(0) ? $models['mfo_model']::where($models['key'], $prs[0]->id)->get() : collect();
        $strats = $prs->get(1) ? $models['mfo_model']::where($models['key'], $prs[1]->id)->get() : collect();
        $supports = $prs->get(2) ? $models['mfo_model']::where($models['key'], $prs[2]->id)->get() : collect();

        $opcrmfodatas = $models['data_model']::all();

        return view("drive.pr", compact('guard', 'opcrmfodatas', 'prs', 'cores', 'strats', 'supports', 'cat', 'empid', 'employees', 'prnumber'));
    }
    
    public function deleteFile($id)
    {
        $document = Document::find($id);
    
        if (!$document) {
            return response()->json(['error' => 'Document not found'], 404);
        }
    
        $folder = DocuFolder::find($document->folder_id);
    
        if (!$folder) {
            return response()->json(['error' => 'Folder not found'], 404);
        }
    
        $filePath = $folder->folder_path . '/' . $document->file;
    
        if (file_exists($filePath)) {
            unlink($filePath);
        } else {
            return response()->json(['error' => 'File not found'], 404);
        }
    
        $document->delete();
    
        return response()->json(['success' => 'File deleted successfully']);
    }
  
}
