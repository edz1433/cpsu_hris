@extends('layouts.master')

@section('body')
<style>  
    #table-form {
        width: 100%;
        font-size: 10px;
    }
    #table-form td, th{
        border: 1px solid rgb(92, 85, 85);
        padding: 1px;
    }
    .b-none{
        border: none !important;
        width: 18px !important;
    }

    .btn-outline-secondary {
        border-radius: 50px;
        width: 30px !important;
        height: 30px !important;
    }
    .border-b-n{
        border-bottom: none;
    }
    .modal-dialog {
        max-width: 90%;
        height: 90%;
        margin: 30px auto;
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

@include('drive.modal-mfo')
<div class="modal fade" id="modal-rating" tabindex="-1" role="dialog" aria-labelledby="modal-prform" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <!-- Placeholder iframe without src initially -->
                <iframe id="rating-iframe" frameborder="0" style="width: 100%; height: 80vh;"></iframe>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
    {{-- Full Name on the Left --}}
    <div class="d-flex align-items-center ml-2">
        <span class="badge bg-primary text-light px-3 py-2 shadow-sm" style="font-size: 0.875rem;">
            <i class="fas fa-user-circle me-1"></i> {{ strtoupper($fullname) }}
        </span>
    </div>

    {{-- Filter & Button on the Right --}}
    <div class="d-flex align-items-center gap-2">
        <div class="input-group" style="width: auto;">
            <select class="form-control form-control-sm" id="categorySelect">
                <option value="0" {{ ($cat == 0) ? 'selected' : '' }}>All</option>
                <option value="1" {{ ($cat == 1) ? 'selected' : '' }}>1st Half</option>
                <option value="2" {{ ($cat == 2) ? 'selected' : '' }}>2nd Half</option>
            </select>
            <div class="input-group-append" style="margin-right: 5px;">
                <span class="input-group-text"><i class="fas fa-filter"></i></span>
            </div>
        </div>

        {{-- <button type="submit" class="btn btn-info btn-sm mr-1" data-toggle="modal" data-target="#modal-rating">
            <i class="fas fa-star"></i> Rating
        </button> --}}
        
        <div class="dropdown d-inline">
            <button class="btn btn-danger btn-sm dropdown-toggle" type="button" id="pdfDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-file-pdf"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="pdfDropdown">
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#modal-rating">Cover Page</a>
                <a class="dropdown-item" href="#" data-toggle="modal" data-cat="2" data-target="#modal-rating">OPCR</a>
            </div>
        </div>
    </div>
</div>

<div style="max-height: 500px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.25rem; padding: 10px;">
<table id="table-form">
    <thead>
        <tr>
            <th rowspan="3" class="text-center">MFO/PAPs</th>
            <th class="text-center" width="180">Success Indicators</th>
            <th rowspan="3" class="text-center">Link to Source</th>
            <th colspan="2" class="text-center" >Evidence</th>
            <th rowspan="3" class="text-center" >Allotted<br>Budget</th>
            <th rowspan="3" class="text-center">Division/<br>Individuals<br>Accountable</th>
            <th rowspan="2"colspan="7" class="text-center border-b-n" >Rating Guide/Accomplishment</th>
            <th class="text-center">Remarks/ Accomplishment</th>
            <th rowspan="2"></th>
        </tr>
        <tr>
            <th rowspan="2" class="text-center">(Targets + Measures)</th>
            <th rowspan="2" class="text-center" width="100">Individual<br>Support<br>Documents</th>
            <th rowspan="2" class="text-center" width="100">Report of<br>Supervisor/<br>Other Offices</th>
            <th rowspan="2"></th>
        </tr>
        <tr>
            <th rowspan="2" class="text-center" width="130">Q</th>
            <th rowspan="2" class="text-center" width="30"></th>
            <th rowspan="2" class="text-center" width="110">E</th>
            <th rowspan="2" class="text-center" width="30"></th>
            <th rowspan="2" class="text-center" width="130">T</th>
            <th rowspan="2" class="text-center" width="30"></th>
            <th rowspan="2" class="text-center" width="30">A</th>
            <th rowspan="2"></th>
        </tr>
    </thead>
    <tbody id="tbody-form" style="max-height: 300px; overflow-y: auto;">
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
            <td></td>
            <td class="text-center"><span class="badge badge-danger rounded-circle">X</span></td>
            @if(isset($prs[0]))
                <td class="b-none text-center">
                    <i class="fas fa-pen fa-md text-success1 pl-1 modalFunction" style="cursor: pointer;"
                    data-toggle="modal"
                    data-cat="1"
                    data-id="{{ $prs[0]->id }}"
                    data-folder="{{ $folder }}"
                    data-target="#createOpcrMfoModal">
                    </i>
                </td>
            @else 
                <td class="b-none text-center"></td>
            @endif
        </tr>

        @foreach($cores as $core)
            <tr>
                <td>
                    @if(displayValue($core->mfo) || displayValue($core->functions) || displayValue($core->percent))
                        {{ displayValue($core->mfo) }} {{ displayValue($core->functions) }} ({{ displayValue($core->percent) }}%)
                    @endif
                </td>
                <td class="text-center">{{ displayValue($core->target) }}</td>
                <td class="text-center">{{ displayValue($core->in_support) }}</td>
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
                <td class="text-center"><span class="badge badge-danger rounded-circle">X</span></td>
                <td class="b-none text-center">
                    <i class="fas fa-plus text-secondary pl-1 mfo-data" data-toggle="modal" style="cursor: pointer;" data-target="#opcrMfoData" data-mfoid="{{ $core->id }}"></i>
                </td>
            </tr>
            
            @php
                $filteredOpcrMfoDatas = in_array($cat, [1, 2])
                    ? $datas->where('opcr_mfo_id', $core->id)->where('category', $cat)->sortBy('order')
                    : $datas->where('opcr_mfo_id', $core->id)->sortBy('order');
            @endphp

            @foreach($filteredOpcrMfoDatas as $opcrmfodata)
                @php
                    $relatedSubordinates = $datasdpcr->where('opcr_mfo_data_id', $opcrmfodata->id);
                    $allHaveEvidence = $relatedSubordinates->every(function ($sub) {
                        return !empty($sub->evidence_file);
                    });
                @endphp

                <tr  id="mfodata{{ $opcrmfodata->id }}-{{ $opcrmfodata->opcr_mfo_id }}" data-group="core{{ $opcrmfodata->opcr_mfo_id }}" onclick="showOpcrMfoData({{ $opcrmfodata->id }},{{ $opcrmfodata->opcr_mfo_id }}, {{ $core->count }})" style="cursor: pointer;">
                    <td class="text-left align-top" width="210">{!! displayValue($opcrmfodata->mfo) !!}</td>
                    <td class="text-left pl-1">
                        {!! preg_replace('/^(\S+)/', '$1 ' . displayValue($opcrmfodata->measure) . '%', displayValue($opcrmfodata->target)) !!}
                    </td>
                    <td class="text-center">
                        @php
                            $inSupportValue = displayValue($opcrmfodata->in_support);
                        @endphp
                        @if($inSupportValue)
                            <a href="{{ $inSupportValue }}" target="_blank" class="text-primary" style="text-decoration: none;">
                                <i class="fas fa-globe fa-2x"></i>
                            </a>
                        @else
                            <span class="text-muted"><i class="fas fa-globe fa-2x"></i></span>
                        @endif
                    </td>
                    <td class="text-left pl-2" width="210" 
                        onmouseover="if(this.querySelector('.dropdown-menu')) this.querySelector('.dropdown-menu').classList.add('show');" 
                        onmouseout="if(this.querySelector('.dropdown-menu')) this.querySelector('.dropdown-menu').classList.remove('show');"
                        onclick="event.stopPropagation();">
                        @php
                            $withEvidenceCount = $relatedSubordinates->where('evidence_file', '!=', '')->count();
                            $totalCount = $relatedSubordinates->count();
                        @endphp
                        @if($totalCount)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" style="width: 95%;" type="button" id="dropdownEvidence{{ $opcrmfodata->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Evidence ({{ $withEvidenceCount }}/{{ $totalCount }})
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownEvidence{{ $opcrmfodata->id }}" style="max-height: 200px; overflow-y: auto;">
                                    @foreach ($relatedSubordinates as $index => $sub)
                                        @php
                                            $hasEvidence = !empty($sub->evidence_file);
                                            $iconClass = $hasEvidence ? 'text-success' : 'text-secondary';
                                            $evidenceUrl = $hasEvidence ? $sub->evidence_file : 'javascript:void(0)';
                                        @endphp

                                        @if ($hasEvidence)
                                            <a class="dropdown-item" href="{{ $evidenceUrl }}" target="_blank" style="text-decoration: none;">
                                                <i class="fas fa-check-circle {{ $iconClass }}"></i>
                                                <b>{{ strtoupper($sub->fullname) }}</b>
                                            </a>
                                        @else
                                            <span class="dropdown-item" style="cursor: default;">
                                                <i class="fas fa-check-circle {{ $iconClass }}"></i>
                                                <b>{{ strtoupper($sub->fullname) }}</b>
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </td>
                    <td class="text-center"></td>
                    <td class="text-center"></td>
                    <td class="text-center">{!! displayValue($opcrmfodata->div_account) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->quality) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->q_score) !!}</td>
                    <td class="text-center">{!! nl2br(e(displayValue($opcrmfodata->efficiency))) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->e_score) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->timeliness) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->t_score) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->average) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->remarks) !!}</td>
                    <td class="text-center">
                        @if ($allHaveEvidence)
                            <span class="badge badge-success rounded-circle">
                                <i class="fas fa-check"></i>
                            </span>
                        @else
                            <span class="badge badge-danger rounded-circle">X</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        @endforeach

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
            <td></td>
            <td class="text-center"><span class="badge badge-danger rounded-circle">X</span></td>
            @if(isset($prs[1]))
                <td class="b-none text-center">
                    <i class="fas fa-pen fa-md text-success1 pl-1 modalFunction" style="cursor: pointer;"
                    data-toggle="modal"
                    data-cat="2"
                    data-id="{{ $prs[1]->id }}"
                    data-folder="{{ $folder }}"
                    data-target="#createOpcrMfoModal">
                    </i>
                </td>
            @else
                <td class="b-none text-center"></td>
            @endif
        </tr>

        @foreach($strats as $strat)
            <tr>
                <td>{{ displayValue($strat->mfo) }} {{ displayValue($strat->functions) }} ({{ displayValue($strat->percent) }}%)</td>
                <td class="text-center">{{ displayValue($strat->target) }}</td>
                <td class="text-center">{{ displayValue($strat->in_support) }}</td>
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
                <td class="text-center"><span class="badge badge-danger rounded-circle">X</span></td>
                <td class="b-none text-center">
                    <i class="fas fa-plus text-secondary pl-1 mfo-data" data-toggle="modal" style="cursor: pointer;" data-target="#opcrMfoData" data-mfoid="{{ $strat->id }}"></i>
                </td>
            </tr>

            @php
                $filteredopcrmfodatas = in_array($cat, [1, 2])
                    ? $datas->where('opcr_mfo_id', $strat->id)->where('category', $cat)->sortBy('order')
                    : $datas->where('opcr_mfo_id', $strat->id)->sortBy('order');
            @endphp

            @foreach($filteredopcrmfodatas as $opcrmfodata)
                @php
                    $relatedSubordinates = $datasdpcr->where('opcr_mfo_data_id', $opcrmfodata->id);
                    $allHaveEvidence = $relatedSubordinates->every(function ($sub) {
                        return !empty($sub->evidence_file);
                    });
                @endphp
                <tr  id="mfodata{{ $opcrmfodata->id }}-{{ $opcrmfodata->opcr_mfo_id }}" data-group="strategic{{ $opcrmfodata->opcr_mfo_id  }}" onclick="showOpcrMfoData({{ $opcrmfodata->id }}, {{ $opcrmfodata->opcr_mfo_id }}, {{ $strat->count }})" style="cursor: pointer;">
                    <td class="text-left align-top" width="210">{!! displayValue($opcrmfodata->mfo) !!}</td>
                    <td class="text-left pl-1">
                        {!! preg_replace('/^(\S+)/', '$1 ' . displayValue($opcrmfodata->measure) . '%', displayValue($opcrmfodata->target)) !!}
                    </td>
                    <td class="text-center">
                        @php
                            $inSupportValue = displayValue($opcrmfodata->in_support);
                        @endphp
                        @if($inSupportValue)
                            <a href="{{ $inSupportValue }}" target="_blank" class="text-primary" style="text-decoration: none;">
                                <i class="fas fa-globe fa-2x"></i>
                            </a>
                        @else
                            <span class="text-muted"><i class="fas fa-globe fa-2x"></i></span>
                        @endif
                    </td>
                    <td class="text-left pl-2" width="210" 
                        onmouseover="if(this.querySelector('.dropdown-menu')) this.querySelector('.dropdown-menu').classList.add('show');" 
                        onmouseout="if(this.querySelector('.dropdown-menu')) this.querySelector('.dropdown-menu').classList.remove('show');"
                        onclick="event.stopPropagation();">
                        @php
                            $withEvidenceCount = $relatedSubordinates->where('evidence_file', '!=', '')->count();
                            $totalCount = $relatedSubordinates->count();
                        @endphp
                        @if($totalCount)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" style="width: 95%;" type="button" id="dropdownEvidence{{ $opcrmfodata->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Evidence ({{ $withEvidenceCount }}/{{ $totalCount }})
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownEvidence{{ $opcrmfodata->id }}" style="max-height: 200px; overflow-y: auto;">
                                    @foreach ($relatedSubordinates as $index => $sub)
                                        @php
                                            $hasEvidence = !empty($sub->evidence_file);
                                            $iconClass = $hasEvidence ? 'text-success' : 'text-secondary';
                                            $evidenceUrl = $hasEvidence ? asset("storage/Evidence/{$sub->evidence_file}") : 'javascript:void(0)';
                                        @endphp

                                        @if ($hasEvidence)
                                            <a class="dropdown-item" href="{{ $evidenceUrl }}" target="_blank" style="text-decoration: none;">
                                                <i class="fas fa-check-circle {{ $iconClass }}"></i>
                                                <b>{{ strtoupper($sub->fullname) }}</b>
                                            </a>
                                        @else
                                            <span class="dropdown-item" style="cursor: default;">
                                                <i class="fas fa-check-circle {{ $iconClass }}"></i>
                                                <b>{{ strtoupper($sub->fullname) }}</b>
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </td>
                    <td class="text-center"></td>
                    <td class="text-center"></td>
                    <td class="text-center">{!! displayValue($opcrmfodata->div_account) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->quality) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->q_score) !!}</td>
                    <td class="text-center">{!! nl2br(e(displayValue($opcrmfodata->efficiency))) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->e_score) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->timeliness) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->t_score) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->average) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->remarks) !!}</td>
                    <td class="text-center">
                        @if ($allHaveEvidence)
                            <span class="badge badge-success rounded-circle">
                                <i class="fas fa-check"></i>
                            </span>
                        @else
                            <span class="badge badge-danger rounded-circle">X</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        @endforeach

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
            <td></td>
            <td class="text-center"><span class="badge badge-danger rounded-circle">X</span></td>
            @if(isset($prs[2]))
                <td class="b-none text-center">
                    <i class="fas fa-pen fa-md text-success1 pl-1 modalFunction" style="cursor: pointer;"
                    data-toggle="modal"
                    data-cat="3"
                    data-id="{{ $prs[2]->id }}"
                    data-folder="{{ $folder }}"
                    data-target="#createOpcrMfoModal">
                    </i>
                </td>
            @endif
        </tr>
        @foreach($supports as $supp)
            <tr>
                <td>{{ displayValue($supp->mfo) }} {{ displayValue($supp->functions) }} ({{ displayValue($supp->percent) }}%)</td>
                <td class="text-center">{{ displayValue($supp->target) }}</td>
                <td class="text-center">{{ displayValue($supp->in_support) }}</td>
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
                <td class="text-center"><span class="badge badge-danger rounded-circle">X</span></td>
                <td class="b-none text-center">
                    <i class="fas fa-plus text-secondary pl-1 mfo-data" data-toggle="modal" style="cursor: pointer;" data-target="#opcrMfoData" data-mfoid="{{ $supp->id }}"></i>
                </td>
            </tr>

            @php
                $filteredopcrmfodatas = in_array($cat, [1, 2])
                    ? $datas->where('opcr_mfo_id', $supp->id)->where('category', $cat)->sortBy('order')
                    : $datas->where('opcr_mfo_id', $supp->id)->sortBy('order');
            @endphp

            @foreach($filteredopcrmfodatas as $opcrmfodata)
                @php
                    $relatedSubordinates = $datasdpcr->where('opcr_mfo_data_id', $opcrmfodata->id);
                    $allHaveEvidence = $relatedSubordinates->every(function ($sub) {
                        return !empty($sub->evidence_file);
                    });
                @endphp
                <tr  id="mfodata{{ $opcrmfodata->id }}-{{ $opcrmfodata->opcr_mfo_id }}" data-group="support{{ $opcrmfodata->opcr_mfo_id  }}" onclick="showOpcrMfoData({{ $opcrmfodata->id }},{{ $opcrmfodata->opcr_mfo_id }}, {{ $supp->count }})" style="cursor: pointer;">
                    <td class="text-left align-top" width="210">{!! displayValue($opcrmfodata->mfo) !!}</td>
                    <td class="text-left pl-1">
                        {!! preg_replace('/^(\S+)/', '$1 ' . displayValue($opcrmfodata->measure) . '%', displayValue($opcrmfodata->target)) !!}
                    </td>
                    <td class="text-center">
                        @php
                            $inSupportValue = displayValue($opcrmfodata->in_support);
                        @endphp
                        @if($inSupportValue)
                            <a href="{{ $inSupportValue }}" target="_blank" class="text-primary" style="text-decoration: none;">
                                <i class="fas fa-globe fa-2x"></i>
                            </a>
                        @else
                            <span class="text-muted"><i class="fas fa-globe fa-2x"></i></span>
                        @endif
                    </td>
                    <td class="text-left pl-2" width="210" 
                        onmouseover="if(this.querySelector('.dropdown-menu')) this.querySelector('.dropdown-menu').classList.add('show');" 
                        onmouseout="if(this.querySelector('.dropdown-menu')) this.querySelector('.dropdown-menu').classList.remove('show');"
                        onclick="event.stopPropagation();">
                        @php
                            $withEvidenceCount = $relatedSubordinates->where('evidence_file', '!=', '')->count();
                            $totalCount = $relatedSubordinates->count();
                        @endphp
                        @if($totalCount)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" style="width: 95%;" type="button" id="dropdownEvidence{{ $opcrmfodata->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Evidence ({{ $withEvidenceCount }}/{{ $totalCount }})
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownEvidence{{ $opcrmfodata->id }}" style="max-height: 200px; overflow-y: auto;">
                                    @foreach ($relatedSubordinates as $index => $sub)
                                        @php
                                            $hasEvidence = !empty($sub->evidence_file);
                                            $iconClass = $hasEvidence ? 'text-success' : 'text-secondary';
                                            $evidenceUrl = $hasEvidence ? asset("storage/Evidence/{$sub->evidence_file}") : 'javascript:void(0)';
                                        @endphp

                                        @if ($hasEvidence)
                                            <a class="dropdown-item" href="{{ $evidenceUrl }}" target="_blank" style="text-decoration: none;">
                                                <i class="fas fa-check-circle {{ $iconClass }}"></i>
                                                <b>{{ strtoupper($sub->fullname) }}</b>
                                            </a>
                                        @else
                                            <span class="dropdown-item" style="cursor: default;">
                                                <i class="fas fa-check-circle {{ $iconClass }}"></i>
                                                <b>{{ strtoupper($sub->fullname) }}</b>
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </td>
                    <td class="text-center"></td>
                    <td class="text-center"></td>
                    <td class="text-center">{!! displayValue($opcrmfodata->div_account) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->quality) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->q_score) !!}</td>
                    <td class="text-center">{!! nl2br(e(displayValue($opcrmfodata->efficiency))) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->e_score) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->timeliness) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->t_score) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->average) !!}</td>
                    <td class="text-center">{!! displayValue($opcrmfodata->remarks) !!}</td>
                    <td class="text-center">
                        @if ($allHaveEvidence)
                            <span class="badge badge-success rounded-circle">
                                <i class="fas fa-check"></i>
                            </span>
                        @else
                            <span class="badge badge-danger rounded-circle">X</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
</div>

<div class="row mt-2 mb-3">
    <div class="col-md-12 text-right mt-3" style="cursor: pointer;">
        <i class="fas fa-cog mr-2" style="font-size: 16px;" data-toggle="modal" data-target="#setupModal"></i>
    </div>
    <div class="col-md-12 text-center">
        <div class="row">
            @foreach ($selectedEmployees as $asignatory)
                @php
                    $fullName = $asignatory->fname . ' ' .
                                ($asignatory->mname ? strtoupper(substr($asignatory->mname, 0, 1)) . '. ' : '') .
                                $asignatory->lname ;
                @endphp
                <div class="col text-center">
                    <div><strong>_________________________________</strong></div>
                    <div><strong>{{ $fullName ?? 'N/A' }}{{ ($asignatory->suffixes) ? ', '.$asignatory->suffixes : '' }}</strong></div>
                    <div>{{ $asignatory->designation ?? 'N/A' }}</div>
                </div>
            @endforeach
        </div>
    </div> 
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
    });

    $(document).on('click', '.modalFunction', function () {
        let cat = $(this).data('cat');
        let id = $(this).data('id');

        $('#opcr-cat').val(cat);
        $('#opcr-id').val(id);

        if(cat == 1) {
            $('#opcr-cat-text').text('CORE FUNCTION');
        } else if(cat == 2) {
            $('#opcr-cat-text').text('STRATEGIC FUNCTION');
        } else {
            $('#opcr-cat-text').text('SUPPORT FUNCTION');
        }
        
        $('#form-data').empty();

        $.ajax({
            url: '{{ route('opcrData') }}',
            method: 'POST',
            data: {
                cat: cat,
                id: id,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                $('#form-data').html(response.html);
            },
            error: function () {
                $('#form-data').html('<p class="text-danger">Failed to load data.</p>');
            }
        });
    });
</script>
<script>
    function saveSetup() {
        // Logic to save the setup
        const signatories = [];
        for (let i = 1; i <= 5; i++) {
            const value = document.getElementById(`signatory${i}`).value;
            if (value) {
                signatories.push(value);
            }
        }
        console.log('Saved Signatories:', signatories);
        $('#setupModal').modal('hide');
    }
</script>
<script>
    $(document).ready(function () {
        $('#employee').select2();

        const groupValues = ['C:2', 'C:3', 'C:4', 'C:5', 'C:6', 'C:7'];

        $('#employee').on('change', function () {
            const selected = $(this).val() || [];

            const hasGroup = selected.some(val => groupValues.includes(val));
            const hasIndividual = selected.some(val => !groupValues.includes(val));

            if (hasGroup && hasIndividual) {
                // Prefer groups: remove individual selections
                const filtered = selected.filter(val => groupValues.includes(val));
                $(this).val(filtered).trigger('change.select2');
            }

            console.log('Currently selected:', $(this).val());
        });
    });
</script>
<script>
    const empid = "{{ $empid ?? '' }}";
    const prnumber = "{{ $prnumber ?? '' }}";

    document.getElementById('categorySelect').addEventListener('change', function () {
        const cat = this.value;

        // Use the route base and replace the params dynamically
        const url = `{{ route('perRatingOpcr', ['cat' => 'CAT_PLACEHOLDER', 'empid' => 'EMPID_PLACEHOLDER', 'prnumber' => 'PR_PLACEHOLDER']) }}`
            .replace('CAT_PLACEHOLDER', cat)
            .replace('EMPID_PLACEHOLDER', empid)
            .replace('PR_PLACEHOLDER', prnumber);

        window.location.href = url;
    });
</script>
<script>
    function showOpcrMfoData(id, mfoid, count) {
        Swal.fire({
            title: 'Choose an action',
            icon: 'question',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: 'Asign',
            denyButtonText: 'Edit',
            cancelButtonText: 'Delete',
            reverseButtons: false,
            customClass: {
                confirmButton: 'btn btn-primary mx-1',
                denyButton: 'btn btn-info mx-1',
                cancelButton: 'btn btn-danger mx-1'
            },
            buttonsStyling: false // Needed to apply Bootstrap styles
        }).then((result) => {
            if (result.isConfirmed) {
                $('#opcr-mfo-data-id').val(id);
                $('#count').val(count);
                $('#asign-to-dpcr').modal('show');
            } else if (result.isDenied) {
                editOpcrData(id);
                $('#opcr-mfo-id').val(mfoid);

                $.ajax({
                    url: `{{ route('opcrmfoEditData', ':id') }}`.replace(':id', id),
                    method: 'GET',
                    success: function (data) {
                        $('#category').val(data.category);
                        $('#opcr_by').val(data.opcr_by);
                        $('#mfo').val(data.mfo);
                        $('#target').val(data.target);
                        $('#measure').val(data.measure);
                        $('#in_support').val(data.in_support);
                        $('#report_sup').val(data.report_sup);
                        $('#div_account').val(data.div_account);
                        $('#quality').val(data.quality);
                        $('#efficiency').val(data.efficiency);
                        $('#timeliness').val(data.timeliness);
                    },
                    error: function () {
                        Swal.fire('Error', 'Unable to fetch data for editing.', 'error');
                    }
                });
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                confirmDeleteOpcrData(id, mfoid);
            }
        });
    }

    function editOpcrData(id) {
        // Set hidden input value
        document.getElementById('opcrdata_id').value = id;

        // Show the modal
        $('#opcrMfoData').modal('show');
    }
    
    function confirmDeleteOpcrData(id,mfoid) {
        Swal.fire({
            title: 'Delete this entry?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Back'
        }).then((result) => {
            if (!result.isConfirmed) return;

            const url = `{{ route('opcrmfoDeleteData', ':id') }}`
                        .replace(':id', id);

            fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin' // Ensures cookies are sent with the request
            })
            .then(response => {
            if (!response.ok) {
                // still try to parse JSON error
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
            })
            .then(data => {
                Swal.fire('Deleted!', data.success, 'success')
                    .then(() => location.reload());
                $(`#mfodata${id}-${mfoid}`).fadeOut(1500, function () {
                    // Optional callback after fadeOut
                });
            })
            .catch(err => {
                const msg = err.message || err.error || 'Something went wrong.';
                Swal.fire('Error!', msg, 'error');
            });
        });
    }

    $(document).on('click', '.mfo-data', function (event) {
        var mfoid = $(this).data('mfoid');
        $('#opcr-mfo-id').val(mfoid);
        $('#opcrdata_id').val(0);

        $('#opcr_by').val('');
        $('#mfo').val('');
        $('#target').val('');
        $('#in_support').val('');
        $('#report_sup').val('');
        $('#div_account').val('');
        $('#quality').val('');
        $('#efficiency').val('');
        $('#timeliness').val('');
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('modal-rating');
        const iframe = document.getElementById('rating-iframe');

        // Define the URLs with Blade
        const iframeSrc = "{{ route('opcrPdf', ['prnumber' => $prnumber, 'userid' => $empid ?? auth()->guard($guard)->user()->id, 'category' => 1]) }}";
        const iframeSrc1 = "{{ route('generateOpcrPdf', ['prnumber' => $prnumber, 'category' => $cat ?? 1, 'userid' => $empid]) }}";

        let selectedCat = null;

        // Capture clicked link's data-cat value
        document.querySelectorAll('.dropdown-item[data-toggle="modal"]').forEach(link => {
            link.addEventListener('click', function () {
                selectedCat = this.getAttribute('data-cat');
            });
        });

        // Listen for modal show event
        $('#modal-rating').on('show.bs.modal', function () {
            if (selectedCat === '2') {
                iframe.src = iframeSrc1;
            } else {
                iframe.src = iframeSrc;
            }
        });

        // Clear iframe src on modal hide
        $('#modal-rating').on('hidden.bs.modal', function () {
            iframe.src = '';
        });
    });
</script>
{{-- <script>
$(function () {
    $("#table-form tbody").sortable({
        update: function (event, ui) {
            let draggedRow = ui.item; 
            let targetRow = draggedRow.prev().length 
                ? draggedRow.prev()   // if it has a row above, use that
                : draggedRow.next();  // otherwise, use the row below

            if (!targetRow.length) return;

            let draggedId = draggedRow.attr("id").split('-')[0].replace("mfodata", "");
            let targetId = targetRow.attr("id").split('-')[0].replace("mfodata", "");

            // alert("Dragged: " + draggedId + "\nTarget: " + targetId); // debug

            $.ajax({
                url: "{{ route('updateOrder') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    dragged_id: draggedId,
                    target_id: targetId,
                    model: 'OpcrMfoData'
                },
                success: function (res) {
                    // alert("Swapped:\n" + JSON.stringify(res));
                },
                error: function (err) {
                    // alert("Error:\n" + err.responseText);
                }
            });
        }
    });
});
</script> --}}
<script>
$(function () {
    $("#table-form tbody").sortable({
        items: "tr",
        sort: function (event, ui) {
            let draggedGroup = ui.item.attr("data-group");   // e.g., core1, core5
            let placeholder = ui.placeholder;

            // Get groups of neighbors
            let prevGroup = placeholder.prev().attr("data-group");
            let nextGroup = placeholder.next().attr("data-group");

            // If both sides exist and neither matches dragged group → block
            if ((prevGroup && prevGroup !== draggedGroup) &&
                (nextGroup && nextGroup !== draggedGroup)) {
                return false;
            }
        },
        update: function (event, ui) {
            let draggedRow = ui.item;
            let draggedGroup = draggedRow.attr("data-group");
            let targetRow = draggedRow.prev().length 
                ? draggedRow.prev()
                : draggedRow.next();

            if (!targetRow.length) return;

            let targetGroup = targetRow.attr("data-group");

            // Final check: cancel if groups differ
            if (draggedGroup !== targetGroup) {
                $(this).sortable("cancel");
                return;
            }

            // Extract IDs
            let draggedId = draggedRow.attr("id").replace("mfodata", "");
            let targetId = targetRow.attr("id").replace("mfodata", "");

            // Send to server
            $.ajax({
                url: "{{ route('updateOrder') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    dragged_id: draggedId,
                    target_id: targetId,
                    model: 'OpcrMfoData'
                }
            });
        }
    });
});
</script>
@endsection