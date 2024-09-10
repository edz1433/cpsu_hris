@extends('layouts.master')

@section('body')
@include('leaves.style')
<section class="content">
<div class="container-fluid">
    <div class="row">
        @include("leaves.side-menu")
        <div class="col-lg-9">
            <div class="card card-info card-outline">
                <div class="card-header">
                    @include("leaves.top-menu")
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        @php
                            $leaveTypes = [
                                1 => 'Vacation Leave',
                                2 => 'Mandatory/Forced Leave',
                                3 => 'Sick Leave',
                                4 => 'Maternity Leave',
                                5 => 'Paternity Leave',
                                6 => 'Special Privilege Leave',
                                7 => 'Solo Parent Leave',
                                8 => 'Study Leave',
                                9 => '10-Day VAWC Leave',
                                10 => 'Rehabilitation Privilege',
                                11 => 'Special Leave Benefits for Women',
                                12 => 'Special Emergency (Calamity) Leave',
                                13 => 'Adoption Leave',
                                14 => 'Others'
                            ];

                            $leavedetails = [
                                1 => 'Within the Philippines',
                                2 => 'Abroad',
                                3 => 'In Hospital',
                                4 => 'Out Patient',
                                5 => "Completion of Master's Degree",
                                6 => 'BAR/Board Examination Review',
                                7 => 'Monetization of Leave Credits',
                                8 => 'Terminal Leave'
                            ];

                            $access = auth()->guard($guard)->user()->access;
                            $accesarray = explode(',', $access);
                        @endphp
                        <div class="tab-pane active" id="timeline">
                            @foreach($leavesapp as $leaves)
                                <div class="timeline timeline-inverse">
                                    <!-- Step 1 -->
                                    <div class="time-label">
                                        <span class="bg-success"><i class="fas fa-user-circle"></i> @if($guard == "web") {{ strtoupper($employee->lname) }}, {{ strtoupper($employee->fname) }} {{ strtoupper($employee->suffix) }}. {{ strtoupper($employee->mname) }} @else me @endif &emsp;</span>
                                    </div>
                                    <div>
                                        <i class="fas fa-stamp bg-info"></i>
                                        <div class="timeline-item">
                                            <span class="time">{{ (isset($leaves->date_filing)) ? \Carbon\Carbon::parse($leaves->date_filing)->format('F j, Y') : '' }}</span>
                                            <h3 class="timeline-header"><a href="#">Leaves Application</a></h3>
                                            <div class="timeline-body">
                                                <span><b>TYPE OF LEAVE TO AVAILED OF :</b> {{ $leaveTypes[$leaves->leave_type] }}</span><br>
                                                <span><b>DETAILS OF LEAVE :</b> {{ $leavedetails[$leaves->leave_purpose] }} ({{ $leaves->leave_detail }})</span><br>
                                                <span><b>INCLUSIVE DATES :</b> {{ $leaves->date_range }}</span><br>
                                                <span><b>DAYS :</b> {{ $leaves->days }}</span><br>
                                                {{-- <button class="btn btn-primary btn-sm mt-2"><i class="fas fa-eye fa-sm"></i> Preview</button> --}}
                                            </div>
                                        </div>
                                    </div>
                        
                                    <!-- Step 2 -->
                                    <div>
                                        @if($leaves->comment_stat == 1)
                                            <i class="fas fa-ban bg-danger"></i>
                                        @else
                                            <i id="status-icon{{ $leaves->id }}" class="fas {{ ($leaves->status == 1) ? 'fa-times bg-secondary' : (($leaves->status == 2 || $leaves->status == 3 || $leaves->status == 4) ? 'fa-check bg-success' : '') }}"></i>
                                        @endif
                                        <div class="timeline-item">
                                            <span class="time">{{ (!empty($leaves->sup_sdate)) ? \Carbon\Carbon::parse($leaves->sup_sdate)->format('F j, Y') : '' }}</span>
                                            <h3 class="timeline-header border-0">
                                                <a href="#">{{ strtoupper($leaves->supervisor_lname) }}, {{ strtoupper($leaves->supervisor_fname) }} {{ strtoupper($leaves->supervisor_suffix) }}. {{ strtoupper($leaves->supervisor_mname) }}</a><br>
                                                <span><i>Immediate Supervisor</i></span>
                                                @if($leaves->comment_stat == 1)<br>
                                                <div class="callout callout-danger" style="margin: 8px 0px 0px 0px !important; padding: 10px !important;">
                                                    <p>{{ $leaves->comment_details }}</p>
                                                    </div>
                                                @endif
                                            </h3>
                                            @if($guard == "employee")
                                                @if($leaves->supervisor == auth()->guard($guard)->user()->id && $leaves->status == 1 && $leaves->comment_stat !== 1)
                                                    <div class="timeline-footer" id="action-button{{ $leaves->id }}">
                                                        <button class="btn btn-success btn-sm approve-leave" data-id="{{ $leaves->id }}" data-by="1"><i class="fas fa-check"></i> Approve</button>
                                                        <button class="btn btn-danger btn-sm disapprove-leave" data-id="{{ $leaves->id }}" data-by="1">Disapprove</button>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>     
                                    
                                    <div>
                                        @if($leaves->comment_stat == 2)
                                            <i class="fas fa-ban bg-danger"></i>
                                        @else
                                        <i id="status-icon1{{ $leaves->id }}" class="fas {{ ($leaves->status == 1 || $leaves->status == 2) ? 'fa-times bg-secondary' : (($leaves->status == 3 || $leaves->status == 4 || $leaves->status == 5) ? 'fa-check bg-success' : '') }}"></i>
                                        @endif
                                        <div class="timeline-item">
                                            <span class="time">{{ (!empty($leaves->hr_sdate)) ? \Carbon\Carbon::parse($leaves->hr_sdate)->format('F j, Y') : '' }}</span>
                                            <h3 class="timeline-header border-0">
                                                <a href="#">{{ strtoupper($setting->hr_lname) }}, {{ strtoupper($setting->hr_fname) }} {{ strtoupper($setting->hr_suffix) }}. {{ strtoupper($setting->hr_mname) }}</a><br>
                                                <span><i>Head, HRMO</i></span>
                                                @if($leaves->comment_stat == 2)<br>
                                                <div class="callout callout-danger" style="margin: 8px 0px 0px 0px !important; padding: 10px !important;">
                                                    <p>{{ $leaves->comment_details }}</p>
                                                    </div>
                                                @endif
                                            </h3>
                                            @if($guard == "web")
                                                @if($leaves->status == 2 && $leaves->comment_stat != 2 && $accesarray[7] == 1 && $leaves->comment_stat !== 2)
                                                    <div class="timeline-footer" id="action-button1{{ $leaves->id }}">
                                                        <button class="btn btn-success btn-sm approve-leave" data-id="{{ $leaves->id }}" data-by="2"><i class="fas fa-check"></i> Approve</button>
                                                        <button class="btn btn-danger btn-sm disapprove-leave" data-id="{{ $leaves->id }}" data-by="2">Disapprove</button>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>  
                        
                                    <div>
                                        @if($leaves->comment_stat == 3)
                                            <i class="fas fa-ban bg-danger"></i>
                                        @else
                                            <i id="status-icon2{{ $leaves->id }}" class="fas {{ ($leaves->status == 1 || $leaves->status == 2 || $leaves->status == 3) ? 'fa-times bg-secondary' : (($leaves->status == 3 || $leaves->status == 4) ? 'fa-check bg-success' : '') }}"></i>
                                        @endif
                                        <div class="timeline-item">
                                            <span class="time">{{ (!empty($leaves->pres_sdate)) ? \Carbon\Carbon::parse($leaves->pres_sdate)->format('F j, Y') : '' }}</span>
                                            <h3 class="timeline-header border-0">
                                                <a href="#">{{ strtoupper($setting->sucpres_lname) }}, {{ strtoupper($setting->sucpres_fname) }} {{ strtoupper($setting->sucpres_suffix) }}{{ isset($setting->sucpres_lname) ? ', Ph. D.' : ''}}</a><br>
                                                <span><i>SUC President</i></span>
                                                @if($leaves->comment_stat == 3)<br>
                                                <div class="callout callout-danger" style="margin: 8px 0px 0px 0px !important; padding: 10px !important;">
                                                    <p>{{ $leaves->comment_details }}</p>
                                                    </div>
                                                @endif
                                            </h3>
                                            @if($guard == "employee")
                                                @if($setting->suc_pres == auth()->guard($guard)->user()->id && $leaves->status == 3 && $leaves->comment_stat !== 3)
                                                    <div class="timeline-footer" id="action-button2{{ $leaves->id }}">
                                                        <button class="btn btn-success btn-sm approve-leave" data-id="{{ $leaves->id }}" data-by="3"><i class="fas fa-check"></i> Approve</button>
                                                        <button class="btn btn-danger btn-sm disapprove-leave" data-id="{{ $leaves->id }}" data-by="3">Disapprove</button>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>

                                    <div>
                                        @if($leaves->comment_stat == 3)
                                            <i class="fas fa-times bg-secondar"></i>
                                        @else
                                        <i class="fas {{ ($leaves->status == 4 || $leaves->status == 5) ? 'fa-check bg-success' : 'fa-times bg-secondary' }} mt-3"></i>
                                        @endif
                                        <a href="#" class="btn @if($leaves->comment_stat == 3) btn-secondary @else {{ ($leaves->status == 4 || $leaves->status == 5) ? 'btn-danger' : 'btn-secondary' }} @endif btn-sm mt-3 ml-5 download"><i class="fas fa-file-pdf"></i> Preview</a>
                                    </div>

                                </div>
                            @endforeach
                            @foreach($leavesapphead as $leaves)
                                <div class="timeline timeline-inverse">
                                    <!-- Step 1 -->
                                    <div class="time-label">
                                        <span class="bg-success"><i class="fas fa-user-circle"></i> {{ strtoupper($leaves->employee_lname) }}, {{ strtoupper($leaves->employee_fname) }} {{ strtoupper($leaves->employee_suffix) }}. {{ strtoupper($leaves->employee_mname) }}</span>
                                    </div>
                                    <div>
                                        <i class="fas fa-stamp bg-info"></i>
                                        <div class="timeline-item">
                                            <span class="time">{{ (isset($leaves->date_filing)) ? \Carbon\Carbon::parse($leaves->date_filing)->format('F j, Y') : '' }}</span>
                                            <h3 class="timeline-header"><a href="#">Leaves Application</a></h3>
                                            <div class="timeline-body">
                                                <span><b>TYPE OF LEAVE TO AVAILED OF :</b> {{ $leaveTypes[$leaves->leave_type] }}</span><br>
                                                <span><b>DETAILS OF LEAVE :</b> {{ $leavedetails[$leaves->leave_purpose] }} ({{ $leaves->leave_detail }})</span><br>
                                                <span><b>INCLUSIVE DATES :</b> {{ $leaves->date_range }}</span><br>
                                                <span><b>DAYS :</b> {{ $leaves->days }}</span><br>
                                                {{-- <button class="btn btn-primary btn-sm mt-2"><i class="fas fa-eye fa-sm"></i> Preview</button> --}}
                                            </div>
                                        </div>
                                    </div>
                        
                                    <!-- Step 2 -->
                                    <div>
                                        @if($leaves->comment_stat == 1)
                                            <i class="fas fa-ban bg-danger"></i>
                                        @else
                                            <i id="status-icon{{ $leaves->id }}" class="fas {{ ($leaves->status == 1) ? 'fa-times bg-secondary' : (($leaves->status == 2 || $leaves->status == 3 || $leaves->status == 4) ? 'fa-check bg-success' : '') }}"></i>
                                        @endif
                                        <div class="timeline-item">
                                            <span class="time">{{ (!empty($leaves->sup_sdate)) ? \Carbon\Carbon::parse($leaves->sup_sdate)->format('F j, Y') : '' }}</span>
                                            <h3 class="timeline-header border-0">
                                                <a href="#">{{ strtoupper($leaves->supervisor_lname) }}, {{ strtoupper($leaves->supervisor_fname) }} {{ strtoupper($leaves->supervisor_suffix) }}. {{ strtoupper($leaves->supervisor_mname) }}</a><br>
                                                <span><i>Immediate Supervisor</i></span>
                                                @if($leaves->comment_stat == 1)<br>
                                                <div class="callout callout-danger" style="margin: 8px 0px 0px 0px !important; padding: 10px !important;">
                                                    <p>{{ $leaves->comment_details }}</p>
                                                    </div>
                                                @endif
                                            </h3>
                                            @if($guard == "employee")
                                                @if($leaves->supervisor == auth()->guard($guard)->user()->id && $leaves->status == 1 && $leaves->comment_stat !== 1)
                                                    <div class="timeline-footer" id="action-button{{ $leaves->id }}">
                                                        <button class="btn btn-success btn-sm approve-leave" data-id="{{ $leaves->id }}" data-by="1"><i class="fas fa-check"></i> Approve</button>
                                                        <button class="btn btn-danger btn-sm disapprove-leave" data-id="{{ $leaves->id }}" data-by="1">Disapprove</button>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>     
                                    
                                    <div>
                                        @if($leaves->comment_stat == 2)
                                            <i class="fas fa-ban bg-danger"></i>
                                        @else
                                        <i id="status-icon1{{ $leaves->id }}" class="fas {{ ($leaves->status == 1 || $leaves->status == 2) ? 'fa-times bg-secondary' : (($leaves->status == 3 || $leaves->status == 4 || $leaves->status == 5) ? 'fa-check bg-success' : '') }}"></i>
                                        @endif
                                        <div class="timeline-item">
                                            <span class="time">{{ (!empty($leaves->sup_sdate)) ? \Carbon\Carbon::parse($leaves->sup_sdate)->format('F j, Y') : '' }}</span>
                                            <h3 class="timeline-header border-0">
                                                <a href="#">{{ strtoupper($setting->hr_lname) }}, {{ strtoupper($setting->hr_fname) }} {{ strtoupper($setting->hr_suffix) }}. {{ strtoupper($setting->hr_mname) }}</a><br>
                                                <span><i>Head, HRMO</i></span>
                                                @if($leaves->comment_stat == 2)<br>
                                                <div class="callout callout-danger" style="margin: 8px 0px 0px 0px !important; padding: 10px !important;">
                                                    <p>{{ $leaves->comment_details }}</p>
                                                    </div>
                                                @endif
                                            </h3>
                                            @if($guard == "web")
                                                @if($leaves->status == 2 && $leaves->comment_stat != 2 && $accesarray[7] == 1 && $leaves->comment_stat !== 2)
                                                    <div class="timeline-footer" id="action-button1{{ $leaves->id }}">
                                                        <button class="btn btn-success btn-sm approve-leave" data-id="{{ $leaves->id }}" data-by="2"><i class="fas fa-check"></i> Approve</button>
                                                        <button class="btn btn-danger btn-sm disapprove-leave" data-id="{{ $leaves->id }}" data-by="2">Disapprove</button>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>  
                        
                                    <div>
                                        @if($leaves->comment_stat == 3)
                                            <i class="fas fa-ban bg-danger"></i>
                                        @else
                                            <i class="fas {{ ($leaves->status == 1 || $leaves->status == 2 || $leaves->status == 3) ? 'fa-times bg-secondary' : (($leaves->status == 3 || $leaves->status == 4) ? 'fa-check bg-success' : '') }}"></i>
                                        @endif
                                        <div class="timeline-item">
                                            <span class="time">{{ (!empty($leaves->pres_sdate)) ? \Carbon\Carbon::parse($leaves->pres_sdate)->format('F j, Y') : '' }}</span>
                                            <h3 class="timeline-header border-0">
                                                <a href="#">{{ strtoupper($setting->sucpres_lname) }}, {{ strtoupper($setting->sucpres_fname) }} {{ strtoupper($setting->sucpres_suffix) }}{{ isset($setting->sucpres_lname) ? ', Ph. D.' : ''}}</a><br>
                                                <span><i>SUC President</i></span>
                                            </h3>
                                            @if($guard == "employee")
                                                @if($setting->suc_pres == auth()->guard($guard)->user()->id && $leaves->status == 3 && $leaves->comment_stat !== 3)
                                                    <div class="timeline-footer">
                                                        <button class="btn btn-success btn-sm approve-leave" data-id="{{ $leaves->id }}" data-by="3"><i class="fas fa-check"></i> Approve</button>
                                                        <button class="btn btn-danger btn-sm disapprove-leave" data-id="{{ $leaves->id }}" data-by="3">Disapprove</button>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>

                                    <div>
                                        @if($leaves->comment_stat == 3)
                                            <i class="fas fa-ban bg-danger"></i>
                                        @else
                                        <i class="fas {{ ($leaves->status == 4 || $leaves->status == 5) ? 'fa-check bg-success' : 'fa-times bg-secondary' }} mt-3"></i>
                                        @endif
                                        <a href="#" class="btn @if($leaves->comment_stat == 3) btn-secondary @else {{ ($leaves->status == 4 || $leaves->status == 5) ? 'btn-danger' : 'btn-secondary' }} @endif btn-sm mt-3 ml-5 download"><i class="fas fa-file-pdf"></i> Preview</a>
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    
                    </div>                    
                </div>
            </div>                        
        </div>
    </div>
</div>
</section>
@endsection