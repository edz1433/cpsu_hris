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

    public function dpcrPdf(Request $request)
    {
        $customPaper = [0, 0, 612, 936];
        $data = [];
        $pdf = \PDF::loadView('drive.dpcr-pdf', compact('data'))
            ->setPaper($customPaper, 'portrait')
            ->setOptions([
                'margin-top' => 0,
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
