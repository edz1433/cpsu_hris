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
use App\Models\SpmsAsignatory;
use App\Models\Dpcr;
use App\Models\DpcrMfo;
use App\Models\DpcrMfoData;
use App\Models\SpmsPersonnel;
use App\Models\Office;

class OpcrController extends Controller
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
    
    public function createOpcr(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'folder_id' => 'required|exists:docu_folders,id',
            'year' => 'required|integer',
        ]);

        $setting = Setting::first();
        $prSetting = PrSetting::orderBy('created_at', 'asc')->first();

        if (!$prSetting) {
            return redirect()->back()->with('error1', 'PR Settings not configured.');
        }

        $folderId = $request->folder_id;

        $lastPr = Opcr::where('pr_number', 'like', 'O-%')
                    ->orderByDesc('id')
                    ->value('pr_number');

        if ($lastPr && preg_match('/O-(\d+)/', $lastPr, $matches)) {
            $nextNumber = (int)$matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        $prNumber = 'O-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);


        // Check for existing OPCR for the same year
        if (Opcr::where('year', $request->year)->exists()) {
            return redirect()->back()->with('error1', 'OPCR already exists for this year!');
        }

        // Get the settings first
        $prSetting = PrSetting::find(1);
        
        // 1. Prepare OPCR parent data
        $opcrRecords = [
            [
                'user_id'   => $setting->suc_pres,
                'folder_id' => $folderId,
                'pr_number' => $prNumber,
                'mfo'       => 'CORE FUNCTIONS',
                'percent'   => $prSetting->core_sum,
                'year'      => $request->year,
            ],
            [
                'user_id'   => $setting->suc_pres,
                'folder_id' => $folderId,
                'pr_number' => $prNumber,
                'mfo'       => 'STRATEGIC FUNCTIONS',
                'percent'   => $prSetting->strat_sum,
                'year'      => $request->year,
            ],
            [
                'user_id'   => $setting->suc_pres,
                'folder_id' => $folderId,
                'pr_number' => $prNumber,
                'mfo'       => 'SUPPORT FUNCTIONS',
                'percent'   => $prSetting->support_sum,
                'year'      => $request->year,
            ]
        ];

        // 2. Insert parent OPCR records and collect IDs
        $insertedIds = [];

        foreach ($opcrRecords as $record) {
            $model = Opcr::create($record);
            $insertedIds[] = $model->id;
        }

        // 3. Use the inserted OPCR IDs
        $firstOpcrId = $insertedIds[0];   // CORE
        $secondOpcrId = $insertedIds[1];  // STRATEGIC
        $thirdOpcrId = $insertedIds[2];   // SUPPORT

        // 4. Prepare and insert OPCR MFOs with dynamic percentages from PrSetting
        $opcrMfoData = [
            // Core functions
            [
                'opcr_id' => $firstOpcrId,
                'mfo'     => 'MFO 1',
                'functions' => '',
                'percent' => $prSetting->core_mfo1 ?? 0,
                'count'   => 1,
            ],
            [
                'opcr_id' => $firstOpcrId,
                'mfo'     => 'MFO 2',
                'functions' => '',
                'percent' => $prSetting->core_mfo2 ?? 0,
                'count'   => 2,
            ],
            [
                'opcr_id' => $firstOpcrId,
                'mfo'     => 'MFO 3',
                'functions' => '',
                'percent' => $prSetting->core_mfo3 ?? 0,
                'count'   => 3,
            ],

            // Strategic functions
            [
                'opcr_id' => $secondOpcrId,
                'mfo'     => 'MFO 4',
                'functions' => '',
                'percent' => $prSetting->strategic_mfo4 ?? 0,
                'count'   => 4,
            ],
            [
                'opcr_id' => $secondOpcrId,
                'mfo'     => 'MFO 5',
                'functions' => '',
                'percent' => $prSetting->strategic_mfo5 ?? 0,
                'count'   => 5,
            ],
            // Support functions
            [
                'opcr_id' => $thirdOpcrId,
                'mfo'     => 'MFO 4',
                'functions' => '',
                'percent' => $prSetting->support_mfo4 ?? 0,
                'count'   => 6,
            ],
            [
                'opcr_id' => $thirdOpcrId,
                'mfo'     => 'MFO 5',
                'functions' => '',
                'percent' => $prSetting->support_mfo5 ?? 0,
                'count'   => 7,
            ]
        ];

        // 5. Insert child records
        OpcrMfo::insert($opcrMfoData);

        // Signatories
        $asignatories = [
            ['pr_number' => $prNumber, 'empid' => 'EMP0001', 'suffixes' => "Ph.D.", 'designation' => 'SUC President II', 'spms_type' => 'OPCR', 'label' => 'Discussed with:'],
            ['pr_number' => $prNumber, 'empid' => 'EMP0131', 'suffixes' => "Ph.D.", 'designation' => 'Director, Quality Assurance', 'spms_type' => 'OPCR', 'label' => 'Assessed by:'],
            ['pr_number' => $prNumber, 'empid' => 'EMP0202', 'suffixes' => "Ph.D.", 'designation' => 'Director, Planning and Development', 'spms_type' => 'OPCR', 'label' => 'Reviewed by:'],
            ['pr_number' => $prNumber, 'empid' => 'EMP0003', 'suffixes' => "Ph.D.", 'designation' => 'Vice President for Academic Affairs', 'spms_type' => 'OPCR', 'label' => 'Reviewed by:'],
            ['pr_number' => $prNumber, 'empid' => 'EMP0002', 'suffixes' => "Ph.D.", 'designation' => 'Vice President for Administration and Finance', 'spms_type' => 'OPCR', 'label' => 'Reviewed by:'],
            ['pr_number' => $prNumber, 'empid' => 'EMP0001', 'suffixes' => "Ph.D.", 'designation' => 'SUC President II', 'spms_type' => 'OPCR', 'label' => 'Final Rating by:'],
        ];

        foreach ($asignatories as $asignatory) {
            SpmsAsignatory::create($asignatory);
        }

        return redirect()->back()->with('success', 'OPCR created successfully!');
    }

    public function updateOpcrMfo(Request $request)
    {
        $request->validate([
            'opcr-id' => 'required|integer',
            'mfo' => 'required|array',
            'mfo.*' => 'string',
            'functions' => 'required|array',
            'functions.*' => 'string|nullable',
            'percent' => 'required|array',
            'percent.*' => 'numeric|min:0|max:100'
        ]);
        
        $cat = $request->input('opcr-cat');
        $opcrId = $request->input('opcr-id');
        $functionArray = $request->input('functions');
        $percentArray = $request->input('percent');
        $countArray = $request->input('counts');

        $totalPercent = array_sum($percentArray);

        $prSetting = PrSetting::find(1);

        // Expected sum based on category
        if ($cat == 1) {
            $expectedPercentSum = $prSetting->core_sum;
        } elseif ($cat == 2) {
            $expectedPercentSum = $prSetting->strat_sum;
        } else {
            $expectedPercentSum = $prSetting->support_sum;
        }
        
        // Compare actual total to expected total
        if ($totalPercent != $expectedPercentSum) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['percent' => 'The total percent must be exactly ' . $expectedPercentSum . '%. Current total: ' . $totalPercent]);
        }

        foreach ($request->input('mfo') as $key => $mfo) {
            $count = $countArray[$key];
            $functionValue = $functionArray[$key] ?? '';
            $percentValue = $percentArray[$key];

            $matchingMfos = OpcrMfo::where('opcr_id', $opcrId)
                ->where('count', $count)
                ->get();

            foreach ($matchingMfos as $existingMfo) {
                $existingMfo->update([
                    'functions' => $functionValue,
                    'percent' => $percentValue,
                ]);
            }
        }

        return redirect()->back()->with('success', 'MFO saved successfully!');
    }

    public function createOpcrMfoData(Request $request)
    {
        $request->validate([
            'opcr_mfo_id' => 'required|integer',
            'opcrdata_id' => 'required|integer',
            'mfo' => 'required|string', 
            'target' => 'nullable|string',
            'in_support' => 'nullable|string',
            'report_sup' => 'nullable|string',
            'div_account' => 'nullable|string',
            'quality' => 'nullable|string',
            'efficiency' => 'nullable|string',
            'timeliness' => 'nullable|string',
            'category' => 'required',
            'opcr_by' => 'required',
        ]);

        $setting = Setting::first();
        
        $opcrId = $request->input('opcr_mfo_id');
        $opcrdataId = $request->input('opcrdata_id');
        $categories = $request->input('category') === 'All' ? [1, 2] : [$request->input('category')];

        if ($opcrdataId == 0) {
            foreach ($categories as $category) {
                OpcrMfoData::create([
                    'opcr_mfo_id' => $opcrId,
                    'mfo' => $request->input('mfo'),
                    'target' => $request->input('target'),
                    'measure' => $request->input('measure'),
                    'in_support' => $request->input('in_support'),
                    'report_sup' => $request->input('report_sup'),
                    'div_account' => $request->input('div_account'),
                    'quality' => $request->input('quality'),
                    'efficiency' => $request->input('efficiency'),
                    'timeliness' => $request->input('timeliness'),
                    'category' => $category,
                    'opcr_by' => $request->input('opcr_by'),
                    'user_id' => $setting->suc_pres,
                ]);
            }
        } else {
            $opcrData = OpcrMfoData::find($opcrdataId);
            if ($opcrData) {
                $opcrData->update([
                    'mfo' => $request->input('mfo'),
                    'target' => $request->input('target'),
                    'measure' => $request->input('measure'),
                    'in_support' => $request->input('in_support'),
                    'report_sup' => $request->input('report_sup'),
                    'div_account' => $request->input('div_account'),
                    'quality' => $request->input('quality'),
                    'efficiency' => $request->input('efficiency'),
                    'timeliness' => $request->input('timeliness'),
                    'category' => $request->input('category'),
                    'opcr_by' => $request->input('opcr_by'),
                ]);
            } else {
                return redirect()->back()->with('error', 'OPCR MFO Data not found!');
            }
        }

        return redirect()->back()->with('success', 'MFO data saved successfully!');
    }

    public function opcrmfoEditData($id){
        $opcrMfoData = OpcrMfoData::find($id);
        if ($opcrMfoData) {
            return response()->json($opcrMfoData);
        } else {
            return response()->json(['error' => 'OPCR MFO Data not found!'], 404);
        }
    }

    public function opcrData(Request $request)
    {
        $cat = $request->input('cat');
        $id = $request->input('id');

        $data = OpcrMfo::where('opcr_id', $id)->get();
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
            $count = $item->count; // Get actual count from the DB

            $html .= '
                <div class="form-row">
                    <input type="hidden" name="counts[]" value="' . $count . '"> <!-- Add this line -->

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

    public function opcrmfoDeleteData(Request $request, $id)
    {
        $entry = OpcrMfoData::find($id);

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

    public function opcrPdf($prnumber, $userid, $category)
    {
        $prnumber = $this->shortDecrypt($prnumber);
        $userid = $this->shortDecrypt($userid);
            
        $prs = Opcr::where('user_id', $userid)
        ->where('pr_number', $prnumber)
        ->get();
        
        $employee = Employee::select('fname', 'lname', 'mname', 'suffix', 'prefix', 'supervisor', 'emp_dept')->find($userid);
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

        $pdf = \PDF::loadView('drive.opcr-pdf', compact('prs', 'images', 'data', 'category', 'employee', 'supervisor', 'office', 'reviewsby', 'approveby'))
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

    public function generateOpcrPdf($prnumber, $cat, $empid = null)
    {
        $folder = 2;
        $guard = $this->getGuard();
        $dempid = $this->shortDecrypt($empid);
        $dprnumber = $this->shortDecrypt($prnumber);

        $dempid = ($empid) ? $dempid : auth()->guard($guard)->user()->id;
        
        $employee = Employee::find($dempid);

        $employees = Employee::where('emp_dept', $employee->emp_dept)->where('emp_status', 1)->get();
        $employeesreg = Employee::where('emp_status', 1)->get();

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

        // Assign joined OPCR data directly to $datas
        $datas = \DB::table('opcr_mfo_data')
            ->join('employees', 'opcr_mfo_data.user_id', '=', 'employees.id')
            ->leftJoin('evidence', function ($join) {
                $join->on('opcr_mfo_data.id', '=', 'evidence.data_id')
                    ->where('evidence.category', '=', 2); // Category 2 for OPCR
            })
            ->select(
                'opcr_mfo_data.*',
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

        $pdf = \PDF::loadView('drive.opcr-pdf-rating', compact('guard', 'datas', 'prs', 'cores', 'folder', 'strats', 'supports', 'employeesreg',
            'cat', 'empid', 'employees', 'fullname', 'dempid', 'prnumber', 'dprnumber'))
            ->setPaper($customPaper, 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
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

    public function assignOpcr(Request $request)
    { 
        $setting = Setting::first();
        $id = $request->opcrid;
        $empIds = $request->empid;
        $count = $request->count;
        $prnumber = $request->prnumber;

        $finalEmpIds = [];

        $sucpresData = Employee::join('spms_personnels', 'employees.id', '=', 'spms_personnels.empid')
            ->where('employees.id', $setting->suc_pres)
            ->select('employees.emp_ID', 'employees.suffix', 'spms_personnels.designation')
            ->first();

        if (!empty($empIds) && str_contains(implode(',', $empIds), 'C:')) {
            $categoryIds = [];

            foreach ($empIds as $empId) {
                $parts = explode(',', $empId);
                foreach ($parts as $part) {
                    if (str_starts_with($part, 'C:')) {
                        $categoryIds[] = substr($part, 2);
                    }
                }
            }

            $finalEmpIds = SpmsPersonnel::whereIn('category', $categoryIds)
                            ->where('empid', '!=', $setting->suc_pres)
                            ->pluck('empid')
                            ->toArray();
        } else {
            foreach ($empIds as $empId) {
                if ($empId != $setting->suc_pres) {
                    $finalEmpIds[] = $empId;
                }
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
            $opcrmfodata = OpcrMfoData::find($id);
            $opcrmfo = OpcrMfo::find($opcrmfodata->opcr_mfo_id ?? null);
            $opcr = Opcr::find($opcrmfo->opcr_id ?? null);
            if (!$opcr) continue;

            $opcrmfos = OpcrMfo::where('opcr_id', $opcr->id)->get();
            $functions = $opcrmfos->pluck('functions')->all();

            $lastPr = Dpcr::where('pr_number', 'like', 'D-%')
                        ->orderByDesc('id')
                        ->value('pr_number');

            $nextNumber = ($lastPr && preg_match('/D-(\d+)/', $lastPr, $matches))
                            ? ((int)$matches[1] + 1)
                            : 1;

            $prNumber = 'D-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $exists = Dpcr::where('user_id', $empid)
                        ->where('op_pr_number', $opcr->pr_number)
                        ->exists();

            if (!$exists) {
                $dpcrRecords = [
                    ['mfo' => 'CORE FUNCTIONS',      'percent' => $prSetting->core_sum ?? 0],
                    ['mfo' => 'STRATEGIC FUNCTIONS', 'percent' => $prSetting->strat_sum ?? 0],
                    ['mfo' => 'SUPPORT FUNCTIONS',   'percent' => $prSetting->support_sum ?? 0],
                ];

                $insertedIds = [];
                foreach ($dpcrRecords as $record) {
                    $model = Dpcr::create([
                        'user_id'      => $empid,
                        'opcr_id'      => $opcrmfo->opcr_id ?? null,
                        'op_pr_number' => $opcr->pr_number,
                        'folder_id'    => 2,
                        'pr_number'    => $prNumber,
                        'mfo'          => $record['mfo'],
                        'percent'      => $record['percent'],
                        'year'         => $opcr->year,
                    ]);
                    $insertedIds[] = $model->id;
                }

                [$firstId, $secondId, $thirdId] = $insertedIds;

                $dpcrMfo = [
                    ['dpcr_id' => $firstId, 'opcr_id' => $opcrmfo->opcr_id, 'mfo' => 'MFO 1', 'percent' => $prSetting->core_mfo1 ?? 0, 'functions' => $functions[0] ?? '', 'count' => 1],
                    ['dpcr_id' => $firstId, 'opcr_id' => $opcrmfo->opcr_id, 'mfo' => 'MFO 2', 'percent' => $prSetting->core_mfo2 ?? 0, 'functions' => $functions[1] ?? '', 'count' => 2],
                    ['dpcr_id' => $firstId, 'opcr_id' => $opcrmfo->opcr_id, 'mfo' => 'MFO 3', 'percent' => $prSetting->core_mfo3 ?? 0, 'functions' => $functions[2] ?? '', 'count' => 3],

                    ['dpcr_id' => $secondId, 'opcr_id' => $opcrmfo->opcr_id, 'mfo' => 'MFO 4', 'percent' => $prSetting->strategic_mfo4 ?? 0, 'functions' => $functions[3] ?? '', 'count' => 4],
                    ['dpcr_id' => $secondId, 'opcr_id' => $opcrmfo->opcr_id, 'mfo' => 'MFO 5', 'percent' => $prSetting->strategic_mfo5 ?? 0, 'functions' => $functions[4] ?? '', 'count' => 5],

                    ['dpcr_id' => $thirdId, 'opcr_id' => $opcrmfo->opcr_id, 'mfo' => 'MFO 4', 'percent' => $prSetting->support_mfo4 ?? 0, 'functions' => $functions[5] ?? '', 'count' => 6],
                    ['dpcr_id' => $thirdId, 'opcr_id' => $opcrmfo->opcr_id, 'mfo' => 'MFO 5', 'percent' => $prSetting->support_mfo5 ?? 0, 'functions' => $functions[6] ?? '', 'count' => 7],
                ];

                DpcrMfo::insert($dpcrMfo);

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
                        'spms_type'   => 'OPCR',
                        'label'       => $asignatory['label'],
                    ]);
                }
            }

$dpcrmfofind = DpcrMfo::join('dpcrs', 'dpcr_mfos.dpcr_id', '=', 'dpcrs.id')
    ->where('dpcrs.user_id', $empid)
    ->where('dpcr_mfos.count', $count)
    ->select('dpcr_mfos.*')
    ->first();

// dd($dpcrmfofind);
            if ($opcrmfodata && $dpcrmfofind) {
                $data = $opcrmfodata->toArray();
                unset($data['id']);

                $data['pr_number'] = $opcrmfo->pr_number ?? null;
                $data['dpcr_mfo_id'] = $dpcrmfofind->id;
                $data['opcr_mfo_data_id'] = $id;
                $data['user_id'] = $empid;

                $exists = DpcrMfoData::where('user_id', $empid)
                            ->where('opcr_mfo_data_id', $id)
                            ->exists();

                if (!$exists) {
                    DpcrMfoData::create($data);
                }
            }
        }

        return redirect()->back()->with('success', 'Assigned successfully!');
    }
}
