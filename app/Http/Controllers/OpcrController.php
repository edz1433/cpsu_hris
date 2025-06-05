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

        // Generate next PR number
        $maxPr = Opcr::max('pr_number');
        $prNumber = $maxPr ? str_pad($maxPr + 1, 4, '0', STR_PAD_LEFT) : '0001';

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
                'count'   => 1,
            ],
            [
                'opcr_id' => $secondOpcrId,
                'mfo'     => 'MFO 5',
                'functions' => '',
                'percent' => $prSetting->strategic_mfo5 ?? 0,
                'count'   => 2,
            ],
            // Support functions
            [
                'opcr_id' => $thirdOpcrId,
                'mfo'     => 'MFO 4',
                'functions' => '',
                'percent' => $prSetting->support_mfo4 ?? 0,
                'count'   => 1,
            ],
            [
                'opcr_id' => $thirdOpcrId,
                'mfo'     => 'MFO 5',
                'functions' => '',
                'percent' => $prSetting->support_mfo5 ?? 0,
                'count'   => 2,
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
            $existingMfo = OpcrMfo::where('opcr_id', $opcrId)->where('count', $key + 1)->first();

            if ($existingMfo) {
                $functionValue = $functionArray[$key] ?? '';
                $existingMfo->update([
                    'functions' => $functionValue,  // <- FIXED
                    'percent' => $percentArray[$key],
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

        $opcrId = $request->input('opcr_mfo_id');
        $opcrdataId = $request->input('opcrdata_id');

        if($opcrdataId == 0){
            OpcrMfoData::create([
                'opcr_mfo_id' => $opcrId,
                'mfo' => $request->input('mfo'),
                'target' => $request->input('target'),
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
            $opcrData = OpcrMfoData::find($opcrdataId);
            if ($opcrData) {
                $opcrData->update([
                    'mfo' => $request->input('mfo'),
                    'target' => $request->input('target'),
                    'in_support' => $request->input('in_support'),
                    'report_sup' => $request->input('report_sup'),
                    'div_account' => $request->input('div_account'),
                    'quality' => $request->input('quality'),
                    'efficiency' => $request->input('efficiency'),
                    'timeliness' => $request->input('timeliness'),
                    'category' => $request->input('category'),
                    'opcr_by' => $request->input('opcr_by'),
                ]);
            }else {
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

    public function asignOpcr(Request $request)
    {
        
    }
}
