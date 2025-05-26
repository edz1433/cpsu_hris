<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\PrData;
use App\Models\Opcr;
use App\Models\OpcrMfo;
use App\Models\OpcrMfoData;
use App\Models\Setting;
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
            'mfo.*' => 'required|string',
            'percent.*' => 'required|integer|min:0|max:100',
        ]);

        $setting = Setting::first();
        $folderId = $request->input('folder_id');
        
        $data = [];
        $prNumber = Opcr::max('pr_number') ? str_pad(Opcr::max('pr_number') + 1, 4, '0', STR_PAD_LEFT) : '0001';

        $exists = Opcr::where('year', $request->year)->exists();
        
        if ($exists) {
            return redirect()->back()->with('error1', 'OPCR Already Exists!');
        }

        foreach ($request->input('mfo') as $index => $mfo) {
            $data[] = [
            'user_id' => $setting->suc_pres,
            'folder_id' => $folderId,
            'pr_number' => $prNumber,
            'mfo' => $mfo,
            'percent' => $request->input('percent')[$index],
            'year' => $request->year,
            ];
        }
        
        Opcr::insert($data);

        $asignatories = [
            ['pr_number' => $prNumber, 'empid' => 'EMP0001', 'suffixes' => 'Ph.D.\r\n', 'designation' => 'SUC President II', 'spms_type' => 'OPCR', 'label' => 'Discussed with:'],
            ['pr_number' => $prNumber, 'empid' => 'EMP0131', 'suffixes' => 'Ph.D.\n', 'designation' => 'Director, Quality Assurance', 'spms_type' => 'OPCR', 'label' => 'Assessed by:'],
            ['pr_number' => $prNumber, 'empid' => 'EMP0202', 'suffixes' => 'Ph.D.', 'designation' => 'Director, Planning and Development', 'spms_type' => 'OPCR', 'label' => 'Reviewed by:'],
            ['pr_number' => $prNumber, 'empid' => 'EMP0003', 'suffixes' => 'Ph.D.', 'designation' => 'Vice President for Academic Affairs', 'spms_type' => 'OPCR', 'label' => 'Reviewed by:'],
            ['pr_number' => $prNumber, 'empid' => 'EMP0002', 'suffixes' => 'Ph.D.', 'designation' => 'Vice President for Administration and Finance', 'spms_type' => 'OPCR', 'label' => 'Reviewed by:'],
            ['pr_number' => $prNumber, 'empid' => 'EMP0001', 'suffixes' => 'Ph.D.', 'designation' => 'SUC President II', 'spms_type' => 'OPCR', 'label' => 'Final Rating by:'],
        ];
        
        foreach ($asignatories as $asignatory) {
            SpmsAsignatory::create($asignatory);
        }
        
        return redirect()->back()->with('success', 'Data saved successfully!');
    }

    public function createOpcrMfo(Request $request)
    {
        $request->validate([
            'opcr-id' => 'required|integer',
            'mfo' => 'required|array',
            'mfo.*' => 'string',
            'percent' => 'required|array',
            'percent.*' => 'numeric|min:0|max:100'
        ]);

        $opcrId = $request->input('opcr-id');
        
        foreach ($request->input('mfo') as $key => $mfo) {
            $existingMfo = OpcrMfo::where('opcr_id', $opcrId)->where('count', $key + 1)->first();

            if ($existingMfo) {
                // Update existing record
                $existingMfo->update([
                    'mfo' => $mfo,
                    'percent' => $request->input('percent')[$key],
                ]);
            } else {
                // Create new record
                OpcrMfo::create([
                    'opcr_id' => $opcrId,
                    'mfo' => $mfo,
                    'percent' => $request->input('percent')[$key],
                    'count' => $key + 1, // Add count starting at 1
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
