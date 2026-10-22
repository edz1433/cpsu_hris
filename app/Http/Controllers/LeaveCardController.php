<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Services\LeaveCardLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PDF;

/**
 * Employee's Leave Card (CPSU-F-HRMO-05), generated on demand from the Credit
 * Ledger and the leave applications. Nothing is stored: the PDF is streamed
 * the same way the DTR is, so it never writes to the database or disk.
 */
class LeaveCardController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function leaveCard(Request $request, $id = null)
    {
        $guard = 'web';
        $this->authorizeAdmin();

        // Admins may view any employee's leave card.
        $employee = Employee::find($id ?: $this->firstEmployeeId());

        $emplalls = Employee::where('emp_status', 1)->orderBy('lname')->orderBy('fname')->get();

        $firstMonth = $employee
            ? LeaveCredit::where('empid', $employee->emp_ID)->min('date')
            : null;

        $from = $request->query('from');
        $to = $request->query('to');
        $generated = $employee && $this->validMonth($from) && $this->validMonth($to) && $from <= $to;

        if (!$generated) {
            $from = $from ?: ($firstMonth ?: Carbon::now()->startOfYear()->format('Y-m'));
            $to = $to ?: Carbon::now()->format('Y-m');
        }

        $pdfUrl = $generated
            ? route('leaveCardPdf', ['employee' => $employee->emp_ID, 'from' => $from, 'to' => $to])
            : null;

        return view('leaves.card', compact('guard', 'employee', 'emplalls', 'from', 'to', 'generated', 'pdfUrl', 'firstMonth'));
    }

    public function leaveCardPdf(Request $request, LeaveCardLedger $ledger)
    {
        $this->authorizeAdmin();

        $request->validate([
            'employee' => 'required',
            'from' => 'required|date_format:Y-m',
            'to' => 'required|date_format:Y-m|after_or_equal:from',
        ]);

        $guard = 'web';
        $empId = $request->input('employee');

        $employee = Employee::where('employees.emp_ID', $empId)
            ->leftjoin('dbcpsupms.offices', 'employees.emp_dept', '=', 'dbcpsupms.offices.id')
            ->select('employees.*', 'dbcpsupms.offices.office_name')
            ->firstOrFail();

        $card = $ledger->build($employee->emp_ID, $request->input('from'), $request->input('to'));

        $pdf = PDF::loadView('leaves.card-pdf', [
            'employee' => $employee,
            'card' => $card,
        ])->setPaper('letter', 'landscape');

        // Footer of the official form, centred on every page like the Excel copy.
        $pdf->render();
        $domPdf = $pdf->getDomPDF();
        $canvas = $domPdf->getCanvas();
        $metrics = $domPdf->getFontMetrics();
        $font = $metrics->getFont('Helvetica');
        $text = 'Doc Control Code: CPSU-F-HRMO-05          Effective Date: 09/12/2018          Page {PAGE_NUM} of {PAGE_COUNT}';
        $width = $metrics->getTextWidth(str_replace(['{PAGE_NUM}', '{PAGE_COUNT}'], '9', $text), $font, 6.8);
        $canvas->page_text(($canvas->get_width() - $width) / 2, $canvas->get_height() - 30, $text, $font, 6.8, [0, 0, 0]);

        $name = preg_replace('/[^A-Za-z0-9 \-]/', '', strtoupper($employee->lname . ' ' . $employee->fname));

        return $pdf->stream("Leave Card - {$name}.pdf");
    }

    private function authorizeAdmin(): void
    {
        $user = auth()->guard('web')->user();

        if (!$user || !in_array($user->role, ['Administrator', 'HR Administrator'], true)) {
            abort(403, 'This page is restricted to HR Administrators.');
        }
    }

    private function validMonth($value)
    {
        return is_string($value) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value);
    }

    /**
     * Fallback for /leave/card with no id: the first active employee,
     * so the shared side-menu always has someone to show.
     */
    private function firstEmployeeId()
    {
        return Employee::where('emp_status', 1)
            ->orderBy('lname')
            ->orderBy('fname')
            ->value('id');
    }
}
