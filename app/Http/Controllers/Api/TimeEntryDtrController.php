<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Dtr;
use App\Models\OfficialTime;
use Carbon\Carbon;
use PDF;

class TimeEntryDtrController extends Controller
{
    private function shortDecrypt($encrypted)
    {
        $key    = 'fA7xB93kL0pTzWmQ';
        $cipher = 'AES-128-ECB';

        $encrypted = strtr($encrypted, '-_', '+/');

        $decrypted = openssl_decrypt(base64_decode($encrypted), $cipher, $key, 0);

        if ($decrypted === false) {
            abort(404, 'Invalid Employee ID');
        }

        return $decrypted;
    }

    public function dtrRead($empid)
    {
        $emp_ID = $this->shortDecrypt($empid);

        $empdata = Employee::where('emp_ID', $emp_ID)
            ->select('id', 'emp_ID', 'fname', 'lname', 'mname')
            ->firstOrFail();

        return view('dtr.app-dtr', [
            'empdata'    => $empdata,
            'period'     => null,
            'date'       => now()->format('Y-m'),
            'overtime'   => 0,
            'dtrFilename'     => null,
            'dtrLogsFilename' => null,
        ]);
    }

    public function dtrSearch(Request $request)
    {
        $request->validate([
            'emp_id' => 'required|exists:employees,emp_ID',
            'period' => 'required|in:1,2,3',
            'date'   => 'required|date_format:Y-m',
        ]);

        $empdata = Employee::where('emp_ID', $request->emp_id)->firstOrFail();

        $period   = (int) $request->period;
        $date     = $request->date;
        $overtime = $request->boolean('overtime');

        $carbonDate   = Carbon::createFromFormat('Y-m', $date);
        $monthName    = strtoupper($carbonDate->format('F'));
        $year         = $carbonDate->format('Y');
        $daysInMonth  = $carbonDate->daysInMonth;

        $periodLabel = match ($period) {
            1 => "1-15",
            2 => "16-$daysInMonth",
            3 => "1-$daysInMonth",
            default => 'Invalid'
        };

        $dtrFilename = strtoupper("{$empdata->fname}_{$empdata->lname}_DTR_{$periodLabel}_{$monthName}_{$year}.pdf");
        $dtrLogsFilename = strtoupper("{$empdata->fname}_{$empdata->lname}_DTR_LOGS_{$periodLabel}_{$monthName}_{$year}.pdf");

        return view('dtr.app-dtr', compact(
            'empdata',
            'period',
            'date',
            'overtime',
            'dtrFilename',
            'dtrLogsFilename'
        ));
    }

    public function dtrPdf($empid, $period, $date, $overtime, $filename)
    {
        $period = (int) $period;
        $overtime = (int) $overtime;

        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);

        // Calculate start and end dates based on the period
        switch ($period) {
            case 1:
                $startDate = Carbon::createFromDate($year, $month, 1);
                $endDate = Carbon::createFromDate($year, $month, 15);
                break;
            case 2:
                $startDate = Carbon::createFromDate($year, $month, 16);
                $endDate = Carbon::createFromDate($year, $month)->endOfMonth();
                break;
            case 3:
                $startDate = Carbon::createFromDate($year, $month, 1);
                $endDate = Carbon::createFromDate($year, $month)->endOfMonth();
                break;
            default:
                abort(400, 'Invalid period');
        }

        // Employee data
        $employee = Employee::where('employees.emp_ID', $empid)
            ->join('campuses', 'employees.camp_id', '=', 'campuses.id')
            ->join('dbcpsupms.offices', 'employees.emp_dept', '=', 'dbcpsupms.offices.id')
            ->select(
                'employees.*',
                'campuses.campus_name',
                'dbcpsupms.offices.office_name'
            )
            ->firstOrFail();

        // Supervisor data (optional)
        $supervisor = $employee->supervisor
            ? Employee::where('id', $employee->supervisor)
                ->select('fname', 'lname', 'mname', 'prefix')
                ->first()
            : null;

        // DTR records
        $dtrRecords = Dtr::where('emp_ID', $empid)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $offtime = OfficialTime::where('empid', $empid)->first();

        $form = ($overtime == 1) ? 'dtr.dtr-pdf-overtime' : 'dtr.dtr-pdf';

        $pdf = PDF::loadView($form, [
            'employee' => $employee,
            'supervisor' => $supervisor,
            'dtrRecords' => $dtrRecords,
            'period' => $period,
            'date' => $date,
            'startDate' => $startDate->format('F j'),
            'endDate' => $endDate->format('j'),
            'year' => $year,
            'offtime' => $offtime,
        ])->setPaper('Legal', 'portrait');

        return $pdf->download($filename);
    }

    // Placeholder — implement this when you create the detailed logs view
    public function dtrLogs(Request $request)
    {
        // Similar logic as dtrPdf, but different view
        // For now return message so link doesn't 404
        return response()->json(['message' => 'DTR Logs PDF generation not yet implemented'], 501);
    }
}