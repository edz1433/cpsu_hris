<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\PrData;
use App\Models\Opcr;
use App\Models\OpcrMfo;
use App\Models\OpcrMfoData;
use App\Models\Setting;
use App\Models\PrSetting;
use App\Models\Dpcr;
use App\Models\DpcrMfo;
use App\Models\DpcrMfoData;
use App\Models\Ipcr;
use App\Models\IpcrMfo;
use App\Models\IpcrMfoData;
use App\Models\SpmsPersonnel;
use App\Models\Office;
use App\Models\SpmsAsignatory;
use App\Models\SpmsComment;

class IpcrController extends Controller
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

    public function updateIpcrMfo(Request $request)
    {
        $request->validate([
            'ipcr-id' => 'required|integer',
            'mfo' => 'required|array',
            'mfo.*' => 'string',
            'functions' => 'required|array',
            'functions.*' => 'string|nullable',
            'percent' => 'required|array',
            'percent.*' => 'numeric|min:0|max:100'
        ]);

        $cat = $request->input('ipcr-cat');
        $ipcrId = $request->input('ipcr-id');
        $functionArray = $request->input('functions');
        $percentArray = $request->input('percent');
        $totalPercent = array_sum($percentArray);

        $ipcr = Ipcr::find($ipcrId);
        $employee = $ipcr ? Employee::find($ipcr->user_id) : null;
        $prSetting = $employee ? PrSetting::find($employee->strat_function) : null;

        if (!$ipcr || !$employee || !$prSetting) {
            return redirect()->back()->with('error', 'IPCR record not found!');
        }

        switch ($cat) {
            case 1:
                $expectedPercentSum = $prSetting->core_sum;
                break;
            case 2:
                $expectedPercentSum = $prSetting->strat_sum;
                break;
            default:
                $expectedPercentSum = $prSetting->support_sum;
        }

        if ($totalPercent != $expectedPercentSum) {
            return back()
                ->withInput()
                ->withErrors([
                    'percent' => "The total percent must be exactly $expectedPercentSum%. Current total: $totalPercent"
                ]);
        }

        $existingMfos = IpcrMfo::where('ipcr_id', $ipcrId)
            ->orderBy('id', 'asc')
            ->get();

        foreach ($existingMfos as $index => $mfoRecord) {
            $mfoRecord->update([
                'functions' => $functionArray[$index] ?? '',
                'percent' => $percentArray[$index],
            ]);
        }

        return redirect()->back()->with('success', 'MFO saved successfully!');
    }

    public function createIpcrMfoData(Request $request)
    {
        $request->validate([
            'ipcr_mfo_id' => 'nullable|integer',
            'dpcr_mfo_id' => 'nullable|integer',
            'ipcrdata_id' => 'nullable|integer',
            'dpcrdata_id' => 'nullable|integer',
            'user_id' => 'nullable',
            'mfo' => 'required|string', 
            'target' => 'nullable|string',
            'measure' => 'nullable|string',
            'in_support' => 'nullable|string',
            'report_sup' => 'nullable|string',
            'div_account' => 'nullable|string',
            'quality' => 'nullable|string',
            'efficiency' => 'nullable|string',
            'timeliness' => 'nullable|string',
            'remarks' => 'nullable|string',
            'category' => 'required',
            'dpcr_by' => 'nullable',
        ]);

        $guard = $this->getGuard();
        $ipcrId = $request->input('ipcr_mfo_id', $request->input('dpcr_mfo_id'));
        $ipcrdataId = $request->input('ipcrdata_id', $request->input('dpcrdata_id', 0));
        $userId = $request->filled('user_id') ? $this->shortDecrypt($request->input('user_id')) : null;

        $categories = $request->input('category') === 'All' ? [1, 2] : [$request->input('category')];
        
        $lastOrder = IpcrMfoData::where('ipcr_mfo_id', $ipcrId)
        ->max('order');

        // Start new order number
        $order = $lastOrder ? $lastOrder + 1 : 1;

        if ($ipcrdataId == 0) {
            if (!$ipcrId) {
                return redirect()->back()->withInput()->withErrors([
                    'ipcr_mfo_id' => 'The IPCR MFO field is required.',
                ]);
            }

            foreach ($categories as $category) {
                IpcrMfoData::create([
                    'ipcr_mfo_id' => $ipcrId,
                    'user_id' => $userId,
                    'mfo' => $request->input('mfo'),
                    'target' => $request->input('target'),
                    'measure' => $request->input('measure'),
                    'in_support' => $request->input('in_support'),
                    'report_sup' => $request->input('report_sup'),
                    'div_account' => $request->input('div_account'),
                    'quality' => $request->input('quality'),
                    'efficiency' => $request->input('efficiency'),
                    'timeliness' => $request->input('timeliness'),
                    'remarks' => $request->input('remarks'),
                    'category' => $category,
                    'dpcr_by' => $request->input('dpcr_by') ?? '',
                    'order' => $order,
                    'lock' => ($guard === 'employee' && $userId && $userId == auth()->guard($guard)->user()->id) ? 2 : 1,
                ]);

                $order++;
            }
        } else {
            // dd($ipcrdataId);
            $ipcrData = IpcrMfoData::find($ipcrdataId);
            if ($ipcrData) {
                $ipcrData->update([
                    'mfo' => $request->input('mfo'),
                    'target' => $request->input('target'),
                    'measure' => $request->input('measure'),
                    'in_support' => $request->input('in_support'),
                    'report_sup' => $request->input('report_sup'),
                    'div_account' => $request->input('div_account'),
                    'quality' => $request->input('quality'),
                    'efficiency' => $request->input('efficiency'),
                    'timeliness' => $request->input('timeliness'),
                    'remarks' => $request->input('remarks'),
                    'category' => $request->input('category'),
                    'dpcr_by' => $request->input('dpcr_by', $ipcrData->dpcr_by),
                ]);
            } else {
                return redirect()->back()->with('error', 'IPCR MFO Data not found!');
            }
        }

        return redirect()->back()->with('success', 'MFO data saved successfully!');
    }

    public function ipcrmfoEditData($id){
        $dpcrMfoData = IpcrMfoData::find($id);
        if ($dpcrMfoData) {
            return response()->json($dpcrMfoData);
        } else {
            return response()->json(['error' => 'DPCR MFO Data not found!'], 404);
        }
    }

    public function ipcrData(Request $request)
    {
        $cat = $request->input('cat');
        $id = $request->input('id');

        $data = IpcrMfo::where('ipcr_id', $id)->get();
        $prSetting = PrSetting::find(1);

        if($cat == 1){
            $prPercent = $prSetting->core_sum;
        }elseif($cat == 2){
            $prPercent = $prSetting->strat_sum;
        }else{
            $prPercent = $prSetting->support_sum;
        }

        // Calculate total percent from the data collection
        $totalPercent = $data->sum('percent');

        // Determine if inputs should be disabled (true if totalPercent != 100)
        $disablePercentInput = ($prPercent == $totalPercent) ? 'readonly' : '';

        $html = '
            <div class="form-row mb-1">
                <div class="form-group col-md-2 d-flex align-items-center" style="margin-bottom: -6px;">
                    <label class="text-success1">MFO\'s</label>
                </div>
                <div class="form-group col-md-8" style="margin-bottom: -6px;">
                    <label class="text-success1">FUNCTIONS</label>
                </div>
                <div class="form-group col-md-2" style="margin-bottom: -6px;">
                    <label class="text-success1">PERCENT</label>
                </div>
        ';

        foreach ($data as $item) {
            $mfo = e($item->mfo);
            $function = $item->functions ?? '';
            $percent = $item->percent ?? 0;

            $html .= '
                    <div class="form-group col-md-2">
                        <input type="text" name="mfo[]" class="form-control form-control-sm text-center"
                            style="height: 52px; font-size: 20px;" value="' . $mfo . '" readonly>
                    </div>
                    <div class="form-group col-md-8">
                        <textarea name="functions[]" rows="2" class="form-control form-control-sm" placeholder="function">' . $function . '</textarea>
                    </div>
                    <div class="form-group col-md-2">
                        <input type="text" name="percent[]" class="form-control form-control-sm text-center"
                            style="height: 52px; font-size: 25px;" value="' . $percent . '" ' . $disablePercentInput . '>
                    </div>
                </div>
            ';
        }

        return response()->json(['html' => $html]);
    }

    public function ipcrPdf($prnumber, $userid, $category)
    {
        $prnumber = $this->shortDecrypt($prnumber);
        $userid = $this->shortDecrypt($userid);
            
        $prs = Ipcr::where('user_id', $userid)
        ->where('pr_number', $prnumber)
        ->get();
        
        $employee = Employee::select('fname', 'lname', 'mname', 'suffix', 'prefix', 'supervisor', 'emp_dept', 'emp_status', 'position')->find($userid);
        $office = Office::select('office_name', 'office_abbr', 'office_head_id')->find($employee->emp_dept);
        $reviewsby = SpmsAsignatory::where('pr_number', $prnumber)
            ->where('label', 'Reviewed by:')
            ->join('employees', 'spms_asignatories.empid', '=', 'employees.emp_ID')
            ->select('spms_asignatories.*', 'employees.fname', 'employees.lname', 'employees.mname', 'employees.suffix', 'employees.prefix')
            ->get();

        $approveby = SpmsAsignatory::where('pr_number', $prnumber)
        ->where('label', 'Approved:')
        ->join('employees', 'spms_asignatories.empid', '=', 'employees.emp_ID')
        ->select('spms_asignatories.*', 'employees.fname', 'employees.lname', 'employees.mname', 'employees.suffix', 'employees.prefix')
        ->get();

        $supervisor = Employee::select('fname', 'lname', 'mname', 'suffix', 'prefix', 'supervisor', 'emp_dept')->where('id', $employee->supervisor)->first();

        $customPaper = [0, 0, 612, 936];

        function imgBase64($filename) {
            $path = public_path("Uploads/$filename");
            if (!file_exists($path)) return null;
            $mime = mime_content_type($path);
            $data = base64_encode(file_get_contents($path));
            return "data:$mime;base64,$data";
        }

        $images = [
            'header' => imgBase64('leave-report-header.png'),
            'img1' => imgBase64('weight-allocation-1.jpg'),
            'img2' => imgBase64('weight-allocation-2.jpg'),
            'img3' => imgBase64('weight-allocation-3.jpg'),
            'img4' => imgBase64('weight-allocation-4.jpg'),
        ];

        $data = [];

        $customPaper = [0, 0, 612, 970];

        $pdf = \PDF::loadView('drive.ipcr-pdf', compact('prs', 'images', 'data', 'category', 'employee', 'supervisor', 'office', 'reviewsby', 'approveby'))
            ->setPaper($customPaper, 'portrait')
            ->setOptions([
                'margin-top' => 10,
                'margin-right' => 10,
                'margin-bottom' => 10,
                'margin-left' => 10,
            ])
            ->setCallbacks([
                'before_render' => function ($domPdf) {
                    $domPdf->getCanvas()->page_text(10, 10, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, [0, 0, 0]);
                },
            ]);

        return $pdf->stream();
    }

    public function generateIpcrPdf($prnumber, $empid, $cat)
    {
        $folder = 2;
        $guard = $this->getGuard();
        $dempid = $this->shortDecrypt($empid);
        $dprnumber = $this->shortDecrypt($prnumber);

        $dempid = ($empid) ? $dempid : auth()->guard($guard)->user()->id;
        
        $employee = Employee::find($dempid);

        $employees = Employee::where('emp_dept', $employee->emp_dept)->get();
        $employeesreg = Employee::all();

        $fullname = $employee
            ? $employee->fname . ' ' .
                ($employee->mname ? strtoupper(substr($employee->mname, 0, 1)) . '.' : '') .
                ' ' . $employee->lname
            : '';

        // Fetch DPCR data
        $prs = Ipcr::where('user_id', $dempid)
            ->where('pr_number', $dprnumber)
            ->get();

        $cores = $prs->get(0) ? IpcrMfo::where('ipcr_id', $prs[0]->id)->get() : collect();
        $strats = $prs->get(1) ? IpcrMfo::where('ipcr_id', $prs[1]->id)->get() : collect();
        $supports = $prs->get(2) ? IpcrMfo::where('ipcr_id', $prs[2]->id)->get() : collect();
        
        // Assign joined DPCR data directly to $datas
        $datas = \DB::table('ipcr_mfo_data')
            ->join('employees', 'ipcr_mfo_data.user_id', '=', 'employees.id')
            ->leftJoin('evidence', function ($join) {
                $join->on('ipcr_mfo_data.id', '=', 'evidence.data_id')
                    ->where('evidence.category', '=', 3); // Category 2 for DPCR
            })
            ->select(
                'ipcr_mfo_data.*',
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

        $customPaper = [0, 0, 1008, 684];

        $pdf = \PDF::loadView('drive.ipcr-pdf-rating', compact('guard', 'datas', 'prs', 'cores', 'folder', 'strats', 'supports', 'employeesreg',
            'cat', 'empid', 'employees', 'fullname', 'dempid', 'prnumber', 'dprnumber'))
            ->setPaper($customPaper, 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'enable_php' => true,
                'margin-top' => 10,
                'margin-right' => 10,
                'margin-bottom' => 10,
                'margin-left' => 10,
            ])
            ->setCallbacks([
                'before_render' => function ($domPdf) {
                    $domPdf->getCanvas()->page_text(10, 10, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, [0, 0, 0]);
                },
            ]);

        return $pdf->stream();
    }
    
    public function assignIpcr(Request $request)
    { 
        $setting = Setting::first();
        $id = $request->dpcrid;
        $empIds = $request->empid;
        $count = $request->count;
        $prnumber = $request->prnumber;

        $finalEmpIds = [];

        $sucpresData = Employee::join('spms_personnels', 'employees.id', '=', 'spms_personnels.empid')
            ->where('employees.id', $setting->suc_pres)
            ->select('employees.emp_ID', 'employees.suffix', 'spms_personnels.designation')
            ->first();

        foreach ($empIds as $empId) {
            if ($empId != $setting->suc_pres) {
                $finalEmpIds[] = $empId;
            }
        }

        foreach ($finalEmpIds as $empid) {
            if (!is_numeric($empid)) continue;

            $employee = Employee::find($empid);
            $empoffice = Office::find($employee->emp_dept);

            $offheadData = Employee::join('spms_personnels', 'employees.id', '=', 'spms_personnels.empid')
                ->where('employees.id', $employee->supervisor)
                ->select('employees.emp_ID', 'employees.suffix', 'spms_personnels.designation', 'employees.emp_dept')
                ->first();

            $headoffice = Office::find($offheadData->emp_dept);

            if (!$employee) continue;
 
            $prSetting = PrSetting::find($employee->strat_function);
            $dpcrmfodata = IpcrMfoData::find($id);
            $dpcrmfo = DpcrMfo::find($dpcrmfodata->dpcr_mfo_id ?? null);
            $dpcr = Dpcr::find($dpcrmfo->dpcr_id ?? null);
            if (!$dpcr) continue;

            $dpcrmfos = DpcrMfo::where('dpcr_id', $dpcr->id)->get();
            $functions = $dpcrmfos->pluck('functions')->all();

            $lastPr = Ipcr::where('pr_number', 'like', 'I-%')
                        ->orderByDesc('id')
                        ->value('pr_number');

            $nextNumber = ($lastPr && preg_match('/I-(\d+)/', $lastPr, $matches))
                            ? ((int)$matches[1] + 1)
                            : 1;

            $prNumber = 'I-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $exists = Ipcr::where('user_id', $empid)
                        ->where('dp_pr_number', $dpcr->pr_number)
                        ->exists();

            if (!$exists) {
                $dpcrRecords = [
                    ['mfo' => 'CORE FUNCTIONS',      'percent' => $prSetting->core_sum ?? 0],
                    ['mfo' => 'STRATEGIC FUNCTIONS', 'percent' => $prSetting->strat_sum ?? 0],
                    ['mfo' => 'SUPPORT FUNCTIONS',   'percent' => $prSetting->support_sum ?? 0],
                ];

                $insertedIds = [];
                foreach ($dpcrRecords as $record) {
                    $model = Ipcr::create([
                        'user_id'      => $empid,
                        'dpcr_id'      => $dpcrmfo->dpcr_id ?? null,
                        'dp_pr_number' => $dpcr->pr_number,
                        'folder_id'    => 3,
                        'pr_number'    => $prNumber,
                        'mfo'          => $record['mfo'],
                        'percent'      => $record['percent'],
                        'year'         => $dpcr->year,
                    ]);
                    $insertedIds[] = $model->id;
                }

                [$firstId, $secondId, $thirdId] = $insertedIds;

                $ipcrMfo = [
                    ['ipcr_id' => $firstId, 'dpcr_id' => $dpcrmfo->dpcr_id, 'mfo' => 'MFO 1', 'percent' => $prSetting->core_mfo1 ?? 0, 'functions' => $functions[0] ?? '', 'count' => 1],
                    ['ipcr_id' => $firstId, 'dpcr_id' => $dpcrmfo->dpcr_id, 'mfo' => 'MFO 2', 'percent' => $prSetting->core_mfo2 ?? 0, 'functions' => $functions[1] ?? '', 'count' => 2],
                    ['ipcr_id' => $firstId, 'dpcr_id' => $dpcrmfo->dpcr_id, 'mfo' => 'MFO 3', 'percent' => $prSetting->core_mfo3 ?? 0, 'functions' => $functions[2] ?? '', 'count' => 3],

                    ['ipcr_id' => $secondId, 'dpcr_id' => $dpcrmfo->dpcr_id, 'mfo' => 'MFO 4', 'percent' => $prSetting->strategic_mfo4 ?? 0, 'functions' => $functions[3] ?? '', 'count' => 4],
                    ['ipcr_id' => $secondId, 'dpcr_id' => $dpcrmfo->dpcr_id, 'mfo' => 'MFO 5', 'percent' => $prSetting->strategic_mfo5 ?? 0, 'functions' => $functions[4] ?? '', 'count' => 5],

                    ['ipcr_id' => $thirdId, 'dpcr_id' => $dpcrmfo->dpcr_id, 'mfo' => 'MFO 4', 'percent' => $prSetting->support_mfo4 ?? 0, 'functions' => $functions[5] ?? '', 'count' => 6],
                    ['ipcr_id' => $thirdId, 'dpcr_id' => $dpcrmfo->dpcr_id, 'mfo' => 'MFO 5', 'percent' => $prSetting->support_mfo5 ?? 0, 'functions' => $functions[6] ?? '', 'count' => 7],
                ];

                IpcrMfo::insert($ipcrMfo);

                $asignatories = [
                    ['empid' => $employee->emp_ID, 'suffixes' => $employee->suffix, 'designation' => !empty($empoffice) && !empty($empoffice->office_name) ? 'Head, '.$empoffice->office_name : '', 'label' => 'Discussed with:'],
                    ['empid' => $offheadData->emp_ID, 'suffixes' => $offheadData->suffix, 'designation' => !empty($headoffice) && !empty($headoffice->office_name) ? $headoffice->office_name : '', 'label' => 'Assessed by:'],
                    ['empid' => 'EMP0131', 'suffixes' => "Ph.D.", 'designation' => 'Performance Management Team', 'label' => 'Reviewed by:'],
                    ['empid' => 'EMP0202', 'suffixes' => "Ph.D.", 'designation' => 'Performance Management Team', 'label' => 'Reviewed by:'],
                    ['empid' => $sucpresData->emp_ID, 'suffixes' => $sucpresData->suffix, 'designation' => 'President', 'label' => 'Approved:'],
                ];

                foreach ($asignatories as $asignatory) {
                    SpmsAsignatory::create([
                        'pr_number'   => $prNumber,
                        'empid'       => $asignatory['empid'],
                        'suffixes'    => $asignatory['suffixes'],
                        'designation' => $asignatory['designation'],
                        'spms_type'   => 'IPCR',
                        'label'       => $asignatory['label'],
                    ]);
                }
            }

            $ipcrmfofind = IpcrMfo::join('ipcrs', 'ipcr_mfos.dpcr_id', '=', 'ipcrs.id')
                ->where('ipcrs.user_id', $empid)
                ->where('ipcr_mfos.count', $count)
                ->select('ipcr_mfos.*')
                ->first();

            // dd($dpcrmfofind);
            if ($dpcrmfodata && $ipcrmfofind) {
                $data = $dpcrmfodata->toArray();
                unset($data['id']);

                $data['pr_number'] = $dpcrmfo->pr_number ?? null;
                $data['ipcr_mfo_id'] = $ipcrmfofind->id;
                $data['dpcr_mfo_data_id'] = $id;
                $data['user_id'] = $empid;
                $data['dpcr_by'] = $data['opcr_by'] ?? $data['dpcr_by'] ?? '';

                $exists = IpcrMfoData::where('user_id', $empid)
                            ->where('dpcr_mfo_data_id', $id)
                            ->exists();

                if (!$exists) {
                    $nextOrder = IpcrMfoData::where('user_id', $empid)
                                    ->where('ipcr_mfo_id', $ipcrmfofind->id)
                                    ->max('order') ?? 0;

                    $data['order'] = $nextOrder + 1;

                    IpcrMfoData::create($data);
                }
            }
        }

        return redirect()->back()->with('success', 'Assigned successfully!');
    }

    public function ipcrmfoDeleteData(Request $request, $id)
    {
        $entry = IpcrMfoData::find($id);

        if (!$entry) {
            return response()->json(['error' => 'OPCR MFO Data not found!'], 404);
        }

        try {
            $entry->delete();
            return response()->json(['success' => 'Entry deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete entry.'], 500);
        }
    }
}
