<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Setting;
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
use App\Models\SpmsPersonnel;

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

    public function perRatingOpcr($cat, $empid, $prnumber)
    {
        $folder = 1;
        $guard = $this->getGuard();
        $dempid = $this->shortDecrypt($empid);
        $dprnumber = $this->shortDecrypt($prnumber);
        $setting = Setting::first();
        $dempid = ($empid) ? $dempid : auth()->guard($guard)->user()->id;
        
        $employees = SpmsPersonnel::join('employees', 'employees.id', '=', 'spms_personnels.empid')
            ->where('employees.emp_status', 1)
            ->where('employees.id', '!=', $setting->suc_pres)
            ->whereIn('spms_personnels.category', [2, 3, 4, 5])
            ->select(
                'spms_personnels.*',
                'employees.id as emp_id',
                'employees.fname',
                'employees.lname',
                'employees.mname',
                'employees.prefix'
            )
            ->get();

        $employee = Employee::find($dempid);

        $fullname = $employee
            ? $employee->fname . ' ' . 
                ($employee->mname ? strtoupper(substr($employee->mname, 0, 1)) . '.' : '') . 
                ' ' . $employee->lname
            : '';

        // Fetch OPCR data
        $prs = Opcr::where('user_id', $dempid)
            ->where('pr_number', $dprnumber)
            ->get();

        $cores = $prs->get(0) ? OpcrMfo::where('opcr_id', $prs[0]->id)->get() : collect();
        $strats = $prs->get(1) ? OpcrMfo::where('opcr_id', $prs[1]->id)->get() : collect();
        $supports = $prs->get(2) ? OpcrMfo::where('opcr_id', $prs[2]->id)->get() : collect();

        $datas = OpcrMfoData::all();

        // Include DPCR-related data (if still needed for the OPCR view)
        $datasdpcr = \DB::table('dpcr_mfo_data')
            ->join('employees', 'dpcr_mfo_data.user_id', '=', 'employees.id')
            ->leftJoin('evidence', function ($join) {
                $join->on('dpcr_mfo_data.id', '=', 'evidence.data_id')
                    ->where('evidence.category', '=', 2); // Category 2 assumed for DPCR
            })
            ->select(
                'dpcr_mfo_data.*',
                'evidence.evidence as evidence_file',
                \DB::raw("CONCAT(employees.fname, ' ', 
                    IF(employees.mname IS NOT NULL AND employees.mname != '', 
                        CONCAT(UPPER(LEFT(employees.mname, 1)), '.'), 
                        ''
                    ), 
                    ' ', employees.lname
                ) AS fullname")
            )
            ->get();

        return view('drive.pr', compact(
            'guard', 'datas', 'prs', 'cores', 'strats', 'supports', 'folder',
            'cat', 'empid', 'employees', 'fullname', 'dempid', 'prnumber', 'datasdpcr'
        ));
    }
    
    public function perRatingDpcr($cat, $empid, $prnumber)
    {
        $folder = 2;
        $guard = $this->getGuard();
        $dempid = $this->shortDecrypt($empid);
        $dprnumber = $this->shortDecrypt($prnumber);

        $dempid = ($empid) ? $dempid : auth()->guard($guard)->user()->id;


        $employee = Employee::find($dempid);

        $employees = Employee::where('emp_dept', $employee->emp_dept)->where('emp_status', 1)->get();

        $fullname = $employee
            ? $employee->fname . ' ' .
                ($employee->mname ? strtoupper(substr($employee->mname, 0, 1)) . '.' : '') .
                ' ' . $employee->lname
            : '';

        // Fetch DPCR data
        $prs = Dpcr::where('user_id', $dempid)
            ->where('pr_number', $dprnumber)
            ->get();

        $cores = $prs->get(0) ? DpcrMfo::where('dpcr_id', $prs[0]->id)->get() : collect();
        $strats = $prs->get(1) ? DpcrMfo::where('dpcr_id', $prs[1]->id)->get() : collect();
        $supports = $prs->get(2) ? DpcrMfo::where('dpcr_id', $prs[2]->id)->get() : collect();

        // Assign joined DPCR data directly to $datas
        $datas = \DB::table('dpcr_mfo_data')
            ->join('employees', 'dpcr_mfo_data.user_id', '=', 'employees.id')
            ->leftJoin('evidence', function ($join) {
                $join->on('dpcr_mfo_data.id', '=', 'evidence.data_id')
                    ->where('evidence.category', '=', 2); // Category 2 for DPCR
            })
            ->select(
                'dpcr_mfo_data.*',
                'evidence.evidence as evidence_file',
                \DB::raw("CONCAT(employees.fname, ' ', 
                    IF(employees.mname IS NOT NULL AND employees.mname != '', 
                        CONCAT(UPPER(LEFT(employees.mname, 1)), '.'), 
                        ''
                    ), 
                    ' ', employees.lname
                ) AS fullname")
            )
            ->get();
            

        return view('drive.pr-dpcr', compact(
            'guard', 'datas', 'prs', 'cores', 'strats', 'supports', 'folder',
            'cat', 'empid', 'employees', 'fullname', 'dempid', 'prnumber'
        ));
    }

    public function perRatingIpcr($cat, $empid, $prnumber){
        $guard = $this->getGuard();
        $dempid = $this->shortDecrypt($empid);
        $dprnumber = $this->shortDecrypt($prnumber);

        $dempid = ($empid) ? $dempid : auth()->guard($guard)->user()->id;
        $employees = Employee::where('emp_status', 1)->get();
        $employee = Employee::where('id', $dempid)->first();

        if ($employee) {
            $middleInitial = $employee->mname ? strtoupper(substr($employee->mname, 0, 1)) . '.' : '';
            $fullname = $employee->fname . ' ' . $middleInitial . ' ' . $employee->lname;
        } else {
            $fullname = '';
        }

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

        $datas = $models['data_model']::all();

        $datasdpcr = \DB::table('dpcr_mfo_data')
            ->join('employees', 'dpcr_mfo_data.user_id', '=', 'employees.id')
            ->leftJoin('evidence', function ($join) {
                $join->on('dpcr_mfo_data.id', '=', 'evidence.data_id')
                    ->where('evidence.category', '=', 2); // Assuming category 2 for DPCR, adjust if needed
            })
            ->select(
                'dpcr_mfo_data.*',
                'evidence.evidence as evidence_file',
                \DB::raw("CONCAT(employees.fname, ' ', 
                    IF(employees.mname IS NOT NULL AND employees.mname != '', 
                        CONCAT(UPPER(LEFT(employees.mname, 1)), '.'), 
                        ''
                    ), 
                    ' ', employees.lname
                ) AS fullname")
            )
            ->get();

        if($prefix == 'O-') {
            $blade = 'pr';
        } elseif($prefix == 'D-') {
            $blade = 'pr-dpcr';
        } else {
            $blade = 'pr-ipcr';
        }

        return view("drive.$blade", compact('guard', 'datas', 'prs', 'cores', 'strats', 'supports', 'cat', 'empid', 'employees', 'fullname', 'dempid', 'prnumber', 'datasdpcr'));
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
