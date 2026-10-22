<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Employee's Leave Card</title>
    {{-- Mirrors public/Uploads/Sample Leave Card.xlsx: landscape, printed at 85%,
         rows 1-11 repeated on every page, column widths in the same proportion. --}}
    <style>
        @page {
            margin: 0.51in 0.51in 1.38in 0.51in;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            margin: 0;
        }
        table.card {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.card thead { display: table-header-group; }
        table.card tr { page-break-inside: avoid; }
        table.card td {
            padding: 0 2px;
            line-height: 1.1;
            overflow: hidden;
        }

        .brand td { padding: 0; }
        .brand-logo { text-align: right; vertical-align: top; padding-right: 4px !important; }
        .brand-logo img { width: 0.6in; height: 0.56in; }
        .brand-text { text-align: center; vertical-align: top; }
        .school { font-weight: bold; font-size: 8.5pt; padding-top: 15pt; }
        .address { font-size: 8.5pt; }
        .title { font-weight: bold; font-size: 10.2pt; text-align: center; height: 13.4pt; vertical-align: middle; }

        .label { font-weight: bold; font-size: 8.5pt; vertical-align: middle; height: 13.4pt; }
        .name { font-weight: bold; font-size: 10.2pt; border-bottom: 1px solid #000; vertical-align: middle; white-space: nowrap; }
        .unit { font-weight: bold; font-size: 10.2pt; border-bottom: 1px solid #000; text-align: center; vertical-align: middle; white-space: nowrap; }

        .head {
            background: #d9d9d9;
            border: 1px solid #000;
            font-weight: bold;
            font-size: 6.8pt;
            text-align: center;
            vertical-align: middle;
        }

        .cell { border: 1px solid #000; height: 10.8pt; vertical-align: top; }
        .num { text-align: center; }
        .text { text-align: left; font-size: 8.5pt; }
        .remarks { font-weight: bold; font-size: 6.8pt; vertical-align: middle; }
        .forward { font-weight: bold; font-size: 6.8pt; vertical-align: middle; white-space: nowrap; }
        .forward-num { font-weight: bold; text-align: center; vertical-align: bottom; }
        .year { background: #ffff00; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
@php
    // Column widths of the Excel template (A..K), in characters.
    $cols = [8.7109375, 28.42578125, 6.7109375, 10, 7.5703125, 10, 6.85546875, 10, 8.28515625, 10, 34.42578125];
    $total = array_sum($cols);
    $pct = array_map(fn ($w) => round($w / $total * 100, 3), $cols);

    $num = fn ($value) => ($value === null || abs((float) $value) < 0.0005) ? '' : number_format((float) $value, 3);
    $balance = fn ($value) => number_format((float) $value, 3);

    $fullName = strtoupper(trim(
        $employee->lname . ', ' . $employee->fname
        . ($employee->mname ? ' ' . $employee->mname : '')
        . ($employee->suffix ? ' ' . $employee->suffix : '')
    ));
    $unit = strtoupper($employee->office_name ?? '');
@endphp

<table class="card">
    <thead>
        {{-- dompdf ignores <col> widths, so the first row fixes them. --}}
        <tr>
            @foreach($pct as $width)
                <td style="width: {{ $width }}%; height: 0; padding: 0;"></td>
            @endforeach
        </tr>
        <tr class="brand">
            <td colspan="11" style="height: 56pt;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="brand-logo" style="width: 31%;">
                            <img src="{{ public_path('Uploads/leave-card-logo.png') }}" alt="">
                        </td>
                        <td class="brand-text" style="width: 38%;">
                            <div class="school">CENTRAL PHILIPPINES STATE UNIVERSITY</div>
                            <div class="address">Kabankalan City, Negros Occidental</div>
                        </td>
                        <td style="width: 31%;"></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="11" class="title">EMPLOYEE'S LEAVE CARD</td>
        </tr>
        <tr><td colspan="11" style="height: 13.4pt;"></td></tr>
        <tr>
            <td class="label">NAME:</td>
            <td colspan="6" class="name">{{ $fullName }}</td>
            <td class="label" style="text-align: center;">UNIT:</td>
            <td colspan="3" class="unit">{{ $unit }}</td>
        </tr>
        <tr><td colspan="11" style="height: 2.5pt;"></td></tr>
        <tr>
            <td rowspan="2" class="head">PERIOD</td>
            <td rowspan="2" class="head">PARTICULARS</td>
            <td colspan="4" class="head" style="height: 10.8pt;">VACATION LEAVE</td>
            <td colspan="4" class="head">SICK LEAVE</td>
            <td rowspan="2" class="head">REMARKS</td>
        </tr>
        <tr>
            <td class="head" style="height: 32.5pt;">EARNED</td>
            <td class="head">ABSENCE UNDERTIME WITH PAY</td>
            <td class="head">BALANCE</td>
            <td class="head">ABSENCE UNDERTIME W/O PAY</td>
            <td class="head">EARNED</td>
            <td class="head">ABSENCE UNDERTIME WITH PAY</td>
            <td class="head">BALANCE</td>
            <td class="head">ABSENCE UNDERTIME W/O PAY</td>
        </tr>
    </thead>
    <tbody>
        @foreach($card['rows'] as $row)
            @if($row['kind'] === 'forward')
                <tr>
                    <td colspan="2" class="cell forward">BALANCE BROUGHT FORWARD</td>
                    <td class="cell"></td>
                    <td class="cell"></td>
                    <td class="cell forward-num">{{ $balance($row['vl_balance']) }}</td>
                    <td class="cell"></td>
                    <td class="cell"></td>
                    <td class="cell"></td>
                    <td class="cell forward-num">{{ $balance($row['sl_balance']) }}</td>
                    <td class="cell"></td>
                    <td class="cell remarks">{{ $row['remarks'] }}</td>
                </tr>
            @else
                <tr>
                    <td class="cell {{ $row['kind'] === 'year' ? 'year' : '' }}">{{ $row['period'] }}</td>
                    <td class="cell text">{{ $row['particulars'] }}</td>
                    <td class="cell num">{{ $num($row['vl_earned']) }}</td>
                    <td class="cell num">{{ $num($row['vl_less']) }}</td>
                    <td class="cell num">{{ $balance($row['vl_balance']) }}</td>
                    <td class="cell num">{{ $num($row['vl_wop']) }}</td>
                    <td class="cell num">{{ $num($row['sl_earned']) }}</td>
                    <td class="cell num">{{ $num($row['sl_less']) }}</td>
                    <td class="cell num">{{ $balance($row['sl_balance']) }}</td>
                    <td class="cell num">{{ $num($row['sl_wop']) }}</td>
                    <td class="cell remarks">{{ $row['remarks'] }}</td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>
</body>
</html>
