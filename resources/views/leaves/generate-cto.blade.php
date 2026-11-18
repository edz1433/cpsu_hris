<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Compensatory Time Off Form</title>
    @php
        $fmt = fn ($h) => \App\Models\LeaveApplication::formatHours($h);
        $name = function ($fname, $mname, $lname, $suffix) {
            return strtoupper(trim(preg_replace('/\s+/', ' ',
                $fname . ' ' . ($mname ? substr($mname, 0, 1) . '.' : '') . ' ' . $lname . ' ' . $suffix)));
        };

        $employeeName = $name($leaveApplication->fname, $leaveApplication->mname, $leaveApplication->lname, $leaveApplication->suffix);
        $signedByOic = $leaveApplication->oic != null;
        $supervisorName = $signedByOic
            ? $name($leaveApplication->oic_fname, $leaveApplication->oic_mname, $leaveApplication->oic_lname, $leaveApplication->oic_suffix)
            : $name($leaveApplication->supervisor_fname, $leaveApplication->supervisor_mname, $leaveApplication->supervisor_lname, $leaveApplication->supervisor_suffix);
        $hrName = $name($leaveApplication->hr_fname, $leaveApplication->hr_mname, $leaveApplication->hr_lname, $leaveApplication->hr_suffix);
        $presName = $name($leaveApplication->president_fname, $leaveApplication->president_mname, $leaveApplication->president_lname, $leaveApplication->president_suffix);
        $presPrefix = $leaveApplication->president_prefix ? ', ' . $leaveApplication->president_prefix : '';

        // Each date draws from what is left after the previous one.
        $dates = \App\Models\LeaveCtoDate::where('leave_application_id', $leaveApplication->lid)->orderBy('cto_date')->get();
        $balance = (float) $leaveApplication->coc_balance;

        $remarkStat = (int) $leaveApplication->remarks_stat;
        // CTO: 2 = declined by the supervisor, 1 = declined by HR, 4 = approved then canceled.
        $statusRemark = [1 => 'Declined', 2 => 'Declined', 4 => 'Cancelled'][$remarkStat] ?? '';
        $statusReason = [1 => $leaveApplication->remarks_details, 2 => $leaveApplication->remarks_details, 4 => $leaveApplication->remarks_details2][$remarkStat] ?? '';

        $date = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('m/d/Y') : '';
        $esign = fn ($signed, $image) => $signed && $image && str_starts_with((string) $image, 'data:image');
    @endphp
    <style>
        @page { margin: 18px 56px; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #000; }
        .head { width: 100%; border-collapse: collapse; }
        .head td { vertical-align: middle; }
        .title { text-align: center; font-weight: bold; font-size: 14px; margin: 8px 0 8px; }
        .info td { padding: 1px 0; font-size: 12px; }
        .line { border-bottom: 1px solid #000; display: inline-block; width: 245px; font-weight: bold; padding-left: 4px; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .grid th, .grid td { border: 1px solid #000; text-align: center; padding: 4px; font-size: 11.5px; }
        .grid th { font-weight: normal; height: 28px; }
        .grid td { height: 18px; }
        .sig { text-align: center; }
        .sig-img { height: 24px; }
        .sig-img img { height: 28px; width: 84px; margin-top: -4px; }
        .sig-line { border-top: 1px solid #000; display: inline-block; min-width: 170px; padding-top: 2px; }
        .sig-name { font-weight: bold; text-decoration: underline; }
        .foot { margin-top: 8px; text-align: center; font-size: 9px; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td style="width: 34%; text-align: right; padding-right: 6px;">
                <img src="{{ public_path('Uploads/leave-card-logo.png') }}" style="height: 50px;">
                <img src="{{ public_path('Uploads/bagong-pilipinas.png') }}" style="height: 50px;">
            </td>
            <td style="line-height: 1.25;">
                Republic of the Philippines<br>
                <b>CENTRAL PHILIPPINES STATE UNIVERSITY</b><br>
                Kabankalan City, Negros Occidental 6111
            </td>
        </tr>
    </table>

    <div class="title">COMPENSATORY TIME OFF FORM</div>

    <table class="info" style="margin-left: 8px;">
        <tr>
            <td style="width: 120px;">Employee's Name:</td>
            <td><span class="line">{{ $employeeName }}</span></td>
        </tr>
        <tr>
            <td>Position/s:</td>
            <td><span class="line">{{ strtoupper($leaveApplication->position) }}</span></td>
        </tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th style="width: 20%;">No. of Hours of<br>Earned COCs</th>
                <th style="width: 20%;">Date of CTO</th>
                <th style="width: 20%;">No. of Requested<br>CTO</th>
                <th style="width: 20%;">Remaining COCs</th>
                <th style="width: 20%;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dates as $i => $row)
                @php $remaining = $balance - $row->hours; @endphp
                <tr>
                    <td>{{ $fmt($balance) }}</td>
                    <td>{{ $date($row->cto_date) }}</td>
                    <td>{{ $fmt($row->hours) }}</td>
                    <td>{{ $fmt($remaining) }}</td>
                    <td>{{ $i === 0 ? $statusRemark : '' }}</td>
                </tr>
                @php $balance = $remaining; @endphp
            @empty
                <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
            @endforelse
        </tbody>
    </table>
    @if($statusRemark && trim((string) $statusReason) !== '')
        <div style="font-size: 10px; margin-top: 4px;"><b>{{ $statusRemark }}:</b> {{ $statusReason }}</div>
    @endif

    {{-- Employee --}}
    <table style="width: 100%; margin-top: 8px;">
        <tr>
            <td style="width: 55%;"></td>
            <td class="sig">
                <div class="sig-img">
                    @if($esign($leaveApplication->emp_esign == 2, $leaveApplication->employee_esign ?? null))
                        <img src="{{ $leaveApplication->employee_esign }}">
                    @endif
                </div>
                <div style="font-weight: bold;">{{ $employeeName }}</div>
                <span class="sig-line" style="min-width: 210px;">Employee's Signature</span><br>
                <div style="margin-top: 2px;">Date: <span style="border-bottom: 1px solid #000; display: inline-block; min-width: 80px;">{{ $date($leaveApplication->date_filing) }}</span></div>
            </td>
        </tr>
    </table>

    <div style="margin-top: 4px;">Recommending Approval:</div>
    <table style="width: 100%; margin-top: 0;">
        <tr>
            <td class="sig" style="width: 50%;">
                <div class="sig-img">
                    @if($signedByOic && $esign(true, $leaveApplication->oic_esign ?? null))
                        <img src="{{ $leaveApplication->oic_esign }}">
                    @elseif(!$signedByOic && $esign($leaveApplication->sup_sign == 2, $leaveApplication->supervisor_esign ?? null))
                        <img src="{{ $leaveApplication->supervisor_esign }}">
                    @endif
                </div>
                <div style="font-weight: bold;">{{ $supervisorName }}</div>
                <span class="sig-line">{{ $signedByOic ? 'Officer-In-Charge' : 'Immediate Supervisor' }}</span><br>
                <div style="margin-top: 2px;">Date: <span style="border-bottom: 1px solid #000; display: inline-block; min-width: 80px;">{{ $date($leaveApplication->sup_sdate) }}</span></div>
            </td>
            <td class="sig" style="width: 50%;">
                <div class="sig-img">
                    @if($esign($leaveApplication->hr_sign == 2, $leaveApplication->hr_esign ?? null))
                        <img src="{{ $leaveApplication->hr_esign }}">
                    @endif
                </div>
                <div class="sig-name">{{ $hrName }}</div>
                <div>Head, HRMO</div>
                <div style="margin-top: 2px;">Date: <span style="border-bottom: 1px solid #000; display: inline-block; min-width: 80px;">{{ $date($leaveApplication->hr_sdate) }}</span></div>
            </td>
        </tr>
    </table>

    <div class="sig" style="margin-top: 6px;">Approved by:</div>
    <div class="sig" style="margin-top: 0;">
        <div class="sig-img">
            @if($esign($leaveApplication->pres_sign == 2, $leaveApplication->president_esign ?? null))
                <img src="{{ $leaveApplication->president_esign }}">
            @endif
        </div>
        <div class="sig-name">{{ $presName }}{{ strtoupper($presPrefix) }}</div>
        <div>SUC President II</div>
    </div>

    <div class="foot">
        Doc Control Code: CPSU-MA-FRM-HRM-28 &nbsp;&nbsp;&nbsp; Effective Date: 10/15/2025 &nbsp;&nbsp;&nbsp; Page No.: 1 of 1
        &nbsp;&nbsp;&nbsp; Ref. #{{ $leaveApplication->transnum }}
    </div>
</body>
</html>
