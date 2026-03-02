<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OFFICE PERFORMANCE COMMITMENT AND REVIEW (OPCR)</title>
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
            ->select('employees.fname', 'employees.lname', 'employees.mname', 'spms_asignatories.*', 'employees.prefix as empprefix')
            ->get();

        function displayValue($value) {
            return strtolower(trim($value ?? '')) === 'n/a' ? '' : $value;
        }
    @endphp
</head>
<body>
    <div style="text-align: center; margin-top: -7px;" class="header">
        <img src="{{ asset('Uploads/spms-header.png') }}" width="25%" alt="Header Image"><br><br>
        <b>OFFICE PERFORMANCE COMMITMENT AND REVIEW (OPCR)</b><br>
        For the Rating Period:@if($cat == 1 || $cat == 0)
                January to June
            @elseif($cat == 2)
                July to December
            @endif
            </span>
            , <span class="underline bold">{{ $prs[0]->year }}</span>.<br>
    </div>
    <div class="table-wrapper">
        <table class="table-form">
            <thead>
                <tr>
                    <th rowspan="5" class="text-center" style="width: 15%;">MFO/PAPs</th>
                    <th rowspan="2" class="text-center" width="120">Success Indicators</th>
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
                    <th rowspan="3" style="width: 10%;" class="text-center">Q</th>
                    <th rowspan="3" class="text-center"></th>
                    <th rowspan="3" style="width: 10%;"class="text-center">E</th>
                    <th rowspan="3" class="text-center"></th>
                    <th rowspan="3" style="width: 10%;"class="text-center">T</th>
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
                            <td style="width: 15%;">
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
                            $filteredOpcrMfoDatas = in_array($cat, [1, 2])
                                ? $datas->where('opcr_mfo_id', $core->id)->where('category', $cat)
                                : $datas->where('opcr_mfo_id', $core->id);
                        @endphp

                        @foreach($filteredOpcrMfoDatas as $Opcrmfodata)
                        @php
                            $relatedSubordinates = $datasdpcr->where('opcr_mfo_data_id', $Opcrmfodata->id);
                            $allHaveEvidence = $relatedSubordinates->every(function ($sub) {
                                return !empty($sub->evidence_file);
                            });
                        @endphp
                        <tr id="mfodata{{ $Opcrmfodata->id }}-{{ $Opcrmfodata->opcr_mfo_id }}" onclick="showOpcrMfoData({{ $Opcrmfodata->id }},{{ $Opcrmfodata->opcr_mfo_id }}, {{ $core->count }})" style="cursor: pointer;">
                            <td class="text-left align-top" style="width: 15%;">{!! displayValue($Opcrmfodata->mfo) !!}</td>
                            <td class="text-left pl-1">
                                {!! displayValue($Opcrmfodata->target) !!}
                            </td>
                            <td class="text-center">
                                {!! displayValue($Opcrmfodata->in_support) !!}
                                {{-- @php
                                    $withEvidenceCount = $relatedSubordinates->where('evidence_file', '!=', '')->count();
                                    $totalCount = $relatedSubordinates->count();
                                @endphp

                                @if($totalCount)
                                    <div class="dropdown">
                                        @foreach ($relatedSubordinates as $sub)
                                            @php
                                                $hasEvidence = !empty($sub->evidence_file);
                                                $iconClass = $hasEvidence ? 'text-success' : 'text-secondary';
                                                $evidenceUrl = $hasEvidence ? $sub->evidence_file : 'javascript:void(0)';
                                                $title = $sub->fullname ?? '';
                                            @endphp

                                            @if ($hasEvidence)
                                                <a class="dropdown-item" href="{{ $evidenceUrl }}" target="_blank">
                                                    <i class="fas fa-check-circle {{ $iconClass }}"></i>
                                                    {{ strtoupper($title) }}<br>
                                                </a>
                                            @else
                                                <span class="dropdown-item" style="cursor: default;">
                                                    <i class="fas fa-check-circle {{ $iconClass }}"></i>
                                                    {{ strtoupper($title) }}<br>
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif --}}
                            </td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->report_sup) !!}</td>
                            <td class="text-center"></td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->div_account) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->quality) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->q_score) !!}</td>
                            <td class="text-center">{!! nl2br(e(displayValue($Opcrmfodata->efficiency))) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->e_score) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->timeliness) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->t_score) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->average) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->remarks) !!}</td>
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
                            $filteredOpcrMfoDatas = in_array($cat, [1, 2])
                                ? $datas->where('opcr_mfo_id', $strat->id)->where('category', $cat)
                                : $datas->where('opcr_mfo_id', $strat->id);
                        @endphp

                        @foreach($filteredOpcrMfoDatas as $Opcrmfodata)
                        @php
                            $relatedSubordinates = $datasdpcr->where('opcr_mfo_data_id', $Opcrmfodata->id);
                            $allHaveEvidence = $relatedSubordinates->every(function ($sub) {
                                return !empty($sub->evidence_file);
                            });
                        @endphp
                        <tr id="mfodata{{ $Opcrmfodata->id }}-{{ $Opcrmfodata->opcr_mfo_id }}" onclick="showOpcrMfoData({{ $Opcrmfodata->id }},{{ $Opcrmfodata->opcr_mfo_id }}, {{ $core->count }})" style="cursor: pointer;">
                            <td class="text-left align-top" style="width: 15%;">{!! displayValue($Opcrmfodata->mfo) !!}</td>
                            <td class="text-left pl-1">
                                {!! displayValue($Opcrmfodata->target) !!}
                            </td>
                            <td class="text-center">
                                {!! displayValue($Opcrmfodata->in_support) !!}
                                {{-- @php
                                    $withEvidenceCount = $relatedSubordinates->where('evidence_file', '!=', '')->count();
                                    $totalCount = $relatedSubordinates->count();
                                @endphp

                                @if($totalCount)
                                    <div class="dropdown">
                                        @foreach ($relatedSubordinates as $sub)
                                            @php
                                                $hasEvidence = !empty($sub->evidence_file);
                                                $iconClass = $hasEvidence ? 'text-success' : 'text-secondary';
                                                $evidenceUrl = $hasEvidence ? $sub->evidence_file : 'javascript:void(0)';
                                                $title = $sub->fullname ?? '';
                                            @endphp

                                            @if ($hasEvidence)
                                                <a class="dropdown-item" href="{{ $evidenceUrl }}" target="_blank">
                                                    <i class="fas fa-check-circle {{ $iconClass }}"></i>
                                                    {{ strtoupper($title) }}<br>
                                                </a>
                                            @else
                                                <span class="dropdown-item" style="cursor: default;">
                                                    <i class="fas fa-check-circle {{ $iconClass }}"></i>
                                                    {{ strtoupper($title) }}<br>
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif --}}
                            </td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->report_sup) !!}</td>
                            <td class="text-center"></td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->div_account) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->quality) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->q_score) !!}</td>
                            <td class="text-center">{!! nl2br(e(displayValue($Opcrmfodata->efficiency))) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->e_score) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->timeliness) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->t_score) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->average) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->remarks) !!}</td>
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
                            $filteredOpcrMfoDatas = in_array($cat, [1, 2])
                                ? $datas->where('opcr_mfo_id', $supp->id)->where('category', $cat)
                                : $datas->where('opcr_mfo_id', $supp->id);
                        @endphp

                        @foreach($filteredOpcrMfoDatas as $Opcrmfodata)
                        @php
                            $relatedSubordinates = $datasdpcr->where('opcr_mfo_data_id', $Opcrmfodata->id);
                            $allHaveEvidence = $relatedSubordinates->every(function ($sub) {
                                return !empty($sub->evidence_file);
                            });
                        @endphp
                        <tr id="mfodata{{ $Opcrmfodata->id }}-{{ $Opcrmfodata->opcr_mfo_id }}" onclick="showOpcrMfoData({{ $Opcrmfodata->id }},{{ $Opcrmfodata->opcr_mfo_id }}, {{ $core->count }})" style="cursor: pointer;">
                            <td class="text-left align-top" style="width: 15%;">{!! displayValue($Opcrmfodata->mfo) !!}</td>
                            <td class="text-left pl-1">
                                {!! displayValue($Opcrmfodata->target) !!}
                            </td>
                            <td class="text-center">
                                {!! displayValue($Opcrmfodata->in_support) !!}
                                {{-- @php
                                    $withEvidenceCount = $relatedSubordinates->where('evidence_file', '!=', '')->count();
                                    $totalCount = $relatedSubordinates->count();
                                @endphp

                                @if($totalCount)
                                    <div class="dropdown">
                                        @foreach ($relatedSubordinates as $sub)
                                            @php
                                                $hasEvidence = !empty($sub->evidence_file);
                                                $iconClass = $hasEvidence ? 'text-success' : 'text-secondary';
                                                $evidenceUrl = $hasEvidence ? $sub->evidence_file : 'javascript:void(0)';
                                                $title = $sub->fullname ?? '';
                                            @endphp

                                            @if ($hasEvidence)
                                                <a class="dropdown-item" href="{{ $evidenceUrl }}" target="_blank">
                                                    <i class="fas fa-check-circle {{ $iconClass }}"></i>
                                                    {{ strtoupper($title) }}<br>
                                                </a>
                                            @else
                                                <span class="dropdown-item" style="cursor: default;">
                                                    <i class="fas fa-check-circle {{ $iconClass }}"></i>
                                                    {{ strtoupper($title) }}<br>
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif --}}
                            </td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->report_sup) !!}</td>
                            <td class="text-center"></td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->div_account) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->quality) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->q_score) !!}</td>
                            <td class="text-center">{!! nl2br(e(displayValue($Opcrmfodata->efficiency))) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->e_score) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->timeliness) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->t_score) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->average) !!}</td>
                            <td class="text-center">{!! displayValue($Opcrmfodata->remarks) !!}</td>
                            <td class="trborder"></td>
                        </tr>
                        @endforeach
                    @endforeach
                    <tr>
                        <td colspan="12" style="text-align: right;">Subtotal</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="13"  style="text-align: right;">Final Rating</td>
                        <td ></td>
                        <td ></td>
                    </tr>
                    <tr>
                       <td colspan="13" style="text-align: right;">Adjectival Rating</td>
                       <td ></td>
                       <td ></td>
                    </tr>
            </tbody>
        </table>
        <table style="width: 100%; border-collapse: collapse; border: none; margin-top: 30px;">
            <tr>
                @foreach ($selectedEmployees as $asignatory)
                    @php
                        // Middle initial (uppercase)
                        $middleInitial = $asignatory->mname 
                            ? strtoupper(substr(trim($asignatory->mname), 0, 1)) . '. ' 
                            : '';

                        // Base name in uppercase
                        $baseName = strtoupper(trim($asignatory->fname . ' ' . $middleInitial . $asignatory->lname));

                        // Prefixes that go BEFORE the name (from your select list)
                        $frontPrefixes = ['Dr.', 'Engr.', 'Atty.', 'RChE.'];

                        $displayName = $baseName;
                        $suffix = '';

                        // Check empprefix value
                        if (!empty($asignatory->empprefix)) {
                            $prefixValue = trim($asignatory->empprefix);

                            if (in_array($prefixValue, $frontPrefixes)) {
                                // BEFORE name
                                $displayName = strtoupper($prefixValue) . ' ' . $baseName;
                            } else {
                                // AFTER name (the rest of your list)
                                $suffix = ', ' . strtoupper($prefixValue);
                                $displayName = $baseName . $suffix;
                            }
                        }

                        // Designation in uppercase
                        $designation = strtoupper($asignatory->designation ?? 'N/A');
                    @endphp

                    <th style="text-align: center; border: none; padding: 10px; font-size: 9.7px;">
                        <div><strong>_________________________________</strong></div>
                        <div>
                            <strong>{{ $displayName ?? 'N/A' }}</strong>
                        </div>
                        <div>{{ $designation }}</div>
                    </th>
                @endforeach
            </tr>
        </table>
    </div>
</body>
{{-- Dynamic Footer Script --}}
<script type="text/php">
    if (isset($pdf)) {
        $pdf->page_script('
            // Skip footer on the last page
            if ($PAGE_NUM == $PAGE_COUNT) {
                return;
            }

            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $size = 6;
            $color = array(0,0,0);
            $word_space = 0.0;
            $char_space = 0.0;
            $angle = 0.0;

            $total_pages = $PAGE_COUNT - 1;

            $footer_text = "Doc Control Code: CPSU-F-HRMO-22        Effective Date: 08/07/2024        Page No.:" . $PAGE_NUM . " of " . $total_pages;

            $text_width = $fontMetrics->get_text_width($footer_text, $font, $size);
            $x = (($pdf->get_width() - $text_width) / 2);
            $y = $pdf->get_height() - 25;

            $pdf->text($x, $y, $footer_text, $font, $size, $color, $word_space, $char_space, $angle);
        ');
    }
</script>
</html>
