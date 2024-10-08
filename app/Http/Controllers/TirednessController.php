<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Dtr;
use App\Models\Fdevice;
use Carbon\Carbon;
use PDF;
use Illuminate\Support\Facades\Route;

class TirednessController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function readTiredness($id = null)
    {
        $guard = $this->getGuard();
        $employeeall = Employee::all();
        $dtr = Dtr::all();
        
        return view('tiredeness.tiredeness', compact('guard', 'employeeall'));
    }

    public function pdfTirednes(Request $request)
    {
        $guard = $this->getGuard();
        $currentRoute = Route::currentRouteName();
        $dtrRecords = Dtr::all();
        $employeeall = Employee::all();
        $form = 'tiredeness.tiredeness-pdf';
        
        $pdf = PDF::loadView($form, [
        'employeeall' => $employeeall,
        'dtrRecords' => $dtrRecords,
        ])->setPaper('Legal', 'portrait');

        return $pdf->stream();
    }

}
