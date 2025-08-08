<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPCR</title>
    <style>
        .header{
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
        }
        /* Web view wrapper for responsive scroll */
        .table-wrapper {
            overflow-x: auto;
            margin: 20px auto;
            max-width: 100%;
            font-family: Arial, Helvetica, sans-serif;
        }

        /* Table styling for both web and PDF */
        .table-form {
            border-collapse: collapse;
            width: 100%;
            min-width: 1000px; /* Allows horizontal scroll on smaller screens */
            font-size: 10px;
        }

        th, td {
            border: 1px solid black;
            padding: 4px;
            text-align: left;
        }

        th {
            /* background-color: #f2f2f2; */
        }

        .text-center {
            text-align: center;
        }

        .border-b-n {
            border-bottom: none;
        }

        @media print {
            body {
                font-size: 10px;
            }
            .table-wrapper {
                overflow: visible !important;
            }
            .table-form {
                min-width: 0;
            }
        }

        .trborder{
            border-top: none !important;
            border-right: none !important;
            border-bottom: none !important;
        }

    .signatories-row {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 40px;
        margin-top: 40px;
    }

    .signatory-col {
        width: 250px;
        text-align: center;
        font-family: Arial, sans-serif;
    }

    .signatory-col .line {
        margin-bottom: 10px;
        font-weight: bold;
    }

    .signatory-col .name {
        font-weight: bold;
        text-transform: uppercase;
    }

    .signatory-col .designation {
        font-size: 14px;
        color: #333;
        margin-top: 4px;
        white-space: normal; /* allows wrapping but not mid-word */
        word-break: break-word;
    }

</style>
    @php
        $selectedEmployees = \App\Models\SpmsAsignatory::where('pr_number', $dprnumber)
            ->join('employees', 'spms_asignatories.empid', '=', 'employees.emp_ID')
            ->select('employees.fname', 'employees.lname', 'employees.mname', 'spms_asignatories.*')
            ->get();

        function displayValue($value) {
            return strtolower(trim($value ?? '')) === 'n/a' ? '' : $value;
        }
    @endphp
</head>
<body>
    <div style="text-align: center; margin-top: -7px;" class="header">
        <img src="{{ asset('Uploads/spms-header.jpg') }}" width="25%" alt="Header Image"><br><br>
        <b>DEPARTMENT PERFORMANCE COMMITMENT AND REVIEW (DPCR)</b><br>
        For the Rating Period:@if($cat == 1 || $cat == 0)
                January - June
            @elseif($cat == 2)
                July - December
            @endif
            </span>
            , <span class="underline bold">{{ $prs[0]->year }}</span>.<br>
    </div>
    <div class="table-wrapper">
        <table class="table-form">
            <thead>
                <tr>
                    <th rowspan="5" class="text-center">MFO/PAPs</th>
                    <th rowspan="2" class="text-center" width="180">Success Indicators</th>
                    <th colspan="2" class="text-center">Evidence</th>
                    <th rowspan="5" class="text-center">Allotted<br>Budget</th>
                    <th rowspan="5" class="text-center">Division/<br>Individuals<br>Accountable</th>
                    <th rowspan="2" colspan="6" class="text-center border-b-n">Rating Guide/Accomplishment</th>
                    <th rowspan="2"></th>
                    <th rowspan="2" class="text-center">Remarks/ Accomplishment</th>
                    <th rowspan="5" class="trborder"></th>
                </tr>
                <tr>
                    <th rowspan="4" class="text-center">Individual<br>Support<br>Documents</th>
                    <th rowspan="4" class="text-center">Report of<br>Supervisor/<br>Other Offices</th>
                </tr>
                <tr>
                    <th rowspan="3" class="text-center">(Targets + Measures)</th>
                    <th rowspan="3" class="text-center">Q</th>
                    <th rowspan="3" class="text-center"></th>
                    <th rowspan="3" class="text-center">E</th>
                    <th rowspan="3" class="text-center"></th>
                    <th rowspan="3" class="text-center">T</th>
                    <th rowspan="3" class="text-center"></th>
                    <th rowspan="3" class="text-center">A</th>
                    <th rowspan="3" class="text-center"></th>
                </tr>
                <tr>
                    <!-- Row 4 (still counted due to rowspan=5 even though empty) -->
                </tr>
                <tr>
                    <!-- Row 5 (still counted due to rowspan=5 even though empty) -->
                </tr>
            </thead>
                <tbody>
                    {{-- CORE PRIORITY MFO HEADER --}}
                    <tr>
                        <td><b>{{ $prs[0]->mfo ?? '' }} ({{ $prs[0]->percent ?? '' }}%)</b></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="trborder"></td>
                    </tr>
                    {{-- Core MFO Rows --}}
                    @foreach($cores as $core)
                        <tr>
                            <td>
                                @if(displayValue($core->mfo) || displayValue($core->functions) || displayValue($core->percent))
                                    {{ displayValue($core->mfo) }} {{ displayValue($core->functions) }} ({{ displayValue($core->percent) }}%)
                                @endif
                            </td>
                            <td class="text-center">{{ displayValue($core->target) }}</td>
                            <td class="text-center"></td>
                            <td class="text-center"></td>
                            <td class="text-center"></td>
                            <td class="text-center"></td>
                            <td class="text-center">{{ displayValue($core->report_sup) }}</td>
                            <td class="text-center">{{ displayValue($core->alloted) }}</td>
                            <td class="text-center">{{ displayValue($core->div_account) }}</td>
                            <td class="text-center">{{ displayValue($core->qrate) }}</td>
                            <td class="text-center">{{ displayValue($core->erate) }}</td>
                            <td class="text-center">{{ displayValue($core->trate) }}</td>
                            <td class="text-center">{{ displayValue($core->a) }}</td>
                            <td class="text-center">{{ displayValue($core->remarks) }}</td>
                            <td class="trborder"></td>
                        </tr>

                        @php
                            $filteredDpcrMfoDatas = in_array($cat, [1, 2])
                                ? $datas->where('dpcr_mfo_id', $core->id)->where('category', $cat)
                                : $datas->where('dpcr_mfo_id', $core->id);
                        @endphp

                        @foreach($filteredDpcrMfoDatas as $dpcrmfodata)
                        <tr id="mfodata{{ $dpcrmfodata->id }}-{{ $dpcrmfodata->dpcr_mfo_id }}" onclick="showOpcrMfoData({{ $dpcrmfodata->id }},{{ $dpcrmfodata->dpcr_mfo_id }}, {{ $core->count }}, {{ $dpcrmfodata->lock }})" style="cursor: pointer;">
                            <td class="text-left align-top" width="210">{!! displayValue($dpcrmfodata->mfo) !!}</td>
                            <td class="text-left pl-1">
                                {!! preg_replace('/^(\S+)/', '$1 ' . displayValue($dpcrmfodata->measure) . '%', displayValue($dpcrmfodata->target)) !!}
                            </td>
                            <td></td>
                            <td class="text-center"></td>
                            <td class="text-center"></td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->div_account) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->quality) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->q_score) !!}</td>
                            <td class="text-center">{!! nl2br(e(displayValue($dpcrmfodata->efficiency))) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->e_score) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->timeliness) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->t_score) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->average) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->remarks) !!}</td>
                            <td class="trborder"></td>
                        </tr>
                        @endforeach
                    @endforeach
                    {{-- STRATEGIC PRIORITY MFO HEADER --}}
                    <tr>
                        <td><b>{{ $prs[1]->mfo ?? '' }} ({{ $prs[1]->percent ?? '' }}%)</b></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="trborder"></td>
                    </tr>

                    {{-- Strategic MFO Rows --}}
                    @foreach($strats as $strat)
                        <tr>
                            <td>
                                @if(displayValue($strat->mfo) || displayValue($strat->functions) || displayValue($strat->percent))
                                    {{ displayValue($strat->mfo) }} {{ displayValue($strat->functions) }} ({{ displayValue($strat->percent) }}%)
                                @endif
                            </td>
                            <td class="text-center">{{ displayValue($strat->target) }}</td>
                            <td class="text-center"></td>
                            <td class="text-center"></td>
                            <td class="text-center"></td>
                            <td class="text-center"></td>
                            <td class="text-center">{{ displayValue($strat->report_sup) }}</td>
                            <td class="text-center">{{ displayValue($strat->alloted) }}</td>
                            <td class="text-center">{{ displayValue($strat->div_account) }}</td>
                            <td class="text-center">{{ displayValue($strat->qrate) }}</td>
                            <td class="text-center">{{ displayValue($strat->erate) }}</td>
                            <td class="text-center">{{ displayValue($strat->trate) }}</td>
                            <td class="text-center">{{ displayValue($strat->a) }}</td>
                            <td class="text-center">{{ displayValue($strat->remarks) }}</td>
                            <td class="trborder"></td>
                        </tr>

                        @php
                            $filteredDpcrMfoDatas = in_array($cat, [1, 2])
                                ? $datas->where('dpcr_mfo_id', $strat->id)->where('category', $cat)
                                : $datas->where('dpcr_mfo_id', $strat->id);
                        @endphp

                        @foreach($filteredDpcrMfoDatas as $dpcrmfodata)
                        <tr id="mfodata{{ $dpcrmfodata->id }}-{{ $dpcrmfodata->dpcr_mfo_id }}" onclick="showOpcrMfoData({{ $dpcrmfodata->id }},{{ $dpcrmfodata->dpcr_mfo_id }}, {{ $core->count }}, {{ $dpcrmfodata->lock }})" style="cursor: pointer;">
                            <td class="text-left align-top" width="210">{!! displayValue($dpcrmfodata->mfo) !!}</td>
                            <td class="text-left pl-1">
                                {!! preg_replace('/^(\S+)/', '$1 ' . displayValue($dpcrmfodata->measure) . '%', displayValue($dpcrmfodata->target)) !!}
                            </td>
                            <td></td>
                            <td class="text-center"></td>
                            <td class="text-center"></td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->div_account) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->quality) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->q_score) !!}</td>
                            <td class="text-center">{!! nl2br(e(displayValue($dpcrmfodata->efficiency))) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->e_score) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->timeliness) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->t_score) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->average) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->remarks) !!}</td>
                            <td class="trborder"></td>
                        </tr>
                        @endforeach
                    @endforeach

                    {{-- SUPPORT PRIORITY MFO HEADER --}}
                    <tr>
                        <td><b>{{ $prs[2]->mfo ?? '' }} ({{ $prs[2]->percent ?? '' }}%)</b></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="trborder"></td>
                    </tr>

                    {{-- SUPPORT MFO Rows --}}
                    @foreach($supports as $supp)
                        <tr>
                            <td>
                                @if(displayValue($supp->mfo) || displayValue($supp->functions) || displayValue($supp->percent))
                                    {{ displayValue($supp->mfo) }} {{ displayValue($supp->functions) }} ({{ displayValue($supp->percent) }}%)
                                @endif
                            </td>
                            <td class="text-center">{{ displayValue($supp->target) }}</td>
                            <td class="text-center"></td>
                            <td class="text-center"></td>
                            <td class="text-center"></td>
                            <td class="text-center"></td>
                            <td class="text-center">{{ displayValue($supp->report_sup) }}</td>
                            <td class="text-center">{{ displayValue($supp->alloted) }}</td>
                            <td class="text-center">{{ displayValue($supp->div_account) }}</td>
                            <td class="text-center">{{ displayValue($supp->qrate) }}</td>
                            <td class="text-center">{{ displayValue($supp->erate) }}</td>
                            <td class="text-center">{{ displayValue($supp->trate) }}</td>
                            <td class="text-center">{{ displayValue($supp->a) }}</td>
                            <td class="text-center">{{ displayValue($supp->remarks) }}</td>
                            <td class="trborder"></td>
                        </tr>

                        @php
                            $filteredDpcrMfoDatas = in_array($cat, [1, 2])
                                ? $datas->where('dpcr_mfo_id', $supp->id)->where('category', $cat)
                                : $datas->where('dpcr_mfo_id', $supp->id);
                        @endphp

                        @foreach($filteredDpcrMfoDatas as $dpcrmfodata)
                        <tr id="mfodata{{ $dpcrmfodata->id }}-{{ $dpcrmfodata->dpcr_mfo_id }}" onclick="showOpcrMfoData({{ $dpcrmfodata->id }},{{ $dpcrmfodata->dpcr_mfo_id }}, {{ $core->count }}, {{ $dpcrmfodata->lock }})" style="cursor: pointer;">
                            <td class="text-left align-top" width="210">{!! displayValue($dpcrmfodata->mfo) !!}</td>
                            <td class="text-left pl-1">
                                {!! preg_replace('/^(\S+)/', '$1 ' . displayValue($dpcrmfodata->measure) . '%', displayValue($dpcrmfodata->target)) !!}
                            </td>
                            <td></td>
                            <td class="text-center"></td>
                            <td class="text-center"></td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->div_account) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->quality) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->q_score) !!}</td>
                            <td class="text-center">{!! nl2br(e(displayValue($dpcrmfodata->efficiency))) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->e_score) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->timeliness) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->t_score) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->average) !!}</td>
                            <td class="text-center">{!! displayValue($dpcrmfodata->remarks) !!}</td>
                            <td class="trborder"></td>
                        </tr>
                        @endforeach
                    @endforeach
            </tbody>
        </table>
        <table class="table-form" style="margin-top: 30px; margin-left: -20px;">
            @foreach ($selectedEmployees as $asignatory)
                @php
                    $fullName = $asignatory->fname . ' ' .
                                ($asignatory->mname ? strtoupper(substr($asignatory->mname, 0, 1)) . '. ' : '') .
                                $asignatory->lname;
                @endphp
                <th style="border: none !important; padding: 1px !important:">
                <div class="signatory-col">
                    <div class="line">_________________________________</div>
                    <div class="name">
                        {{ $fullName ?? 'N/A' }}{{ $asignatory->suffixes ? ', ' . $asignatory->suffixes : '' }}
                    </div>
                    <div class="designation">
                        {{ ucwords(strtolower($asignatory->designation)) ?? 'N/A' }}
                    </div>
                </div>
                </th>
            @endforeach
        </table>
    </div>
<div class="signatories-row">

</div>

</body>
</html>
