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
use App\Models\SpmsPersonnel;
use App\Models\Office;
use App\Models\SpmsAsignatory;

class DpcrController extends Controller
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

    public function createDpcrMfoData(Request $request)
    {
        $request->validate([
            'opcr_mfo_id' => 'required|integer',
            'opcrdata_id' => 'required|integer',
            'user_id' => 'required',
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

        $guard = $this->getGuard();
        $dpcrId = $request->input('opcr_mfo_id');
        $dpcrdataId = $request->input('opcrdata_id');
        $userId = $this->shortDecrypt($request->input('user_id'));

        $categories = $request->input('category') === 'All' ? [1, 2] : [$request->input('category')];

        if ($dpcrdataId == 0) {
            foreach ($categories as $category) {
                DpcrMfoData::create([
                    'dpcr_mfo_id' => $dpcrId,
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
                    'category' => $category,
                    'opcr_by' => $request->input('opcr_by'),
                    'lock' => ($guard === 'employee' && $userId == auth()->guard($guard)->user()->id) ? 2 : 1,
                ]);
            }
        } else {
            // dd($dpcrdataId);
            $dpcrData = DpcrMfoData::find($dpcrdataId);
            if ($dpcrData) {
                $dpcrData->update([
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

    public function dpcrmfoEditData($id){
        $dpcrMfoData = DpcrMfoData::find($id);
        if ($dpcrMfoData) {
            return response()->json($dpcrMfoData);
        } else {
            return response()->json(['error' => 'DPCR MFO Data not found!'], 404);
        }
    }

    public function dpcrData(Request $request)
    {
        $cat = $request->input('cat');
        $id = $request->input('id');

        $data = DpcrMfo::where('dpcr_id', $id)->get();
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

    public function dpcrPdf($prnumber, $userid, $category)
    {
        $prnumber = $this->shortDecrypt($prnumber);
        $userid = $this->shortDecrypt($userid);
            
        $prs = Dpcr::where('user_id', $userid)
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

        $pdf = \PDF::loadView('drive.dpcr-pdf', compact('prs', 'images', 'data', 'category', 'employee', 'supervisor', 'office', 'reviewsby', 'approveby'))
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

    public function generateDpcrPdf($prnumber, $empid, $cat)
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

        $customPaper = [0, 0, 1008, 684];

        $pdf = \PDF::loadView('drive.dpcr-pdf-rating', compact('guard', 'datas', 'prs', 'cores', 'folder', 'strats', 'supports', 'employeesreg',
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
}
