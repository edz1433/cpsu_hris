@extends('layouts.master')

@section('body')
@include('leaves.style')
@php
    // [value, name, legal basis, element id used by the leave script, available]
    $leaveTypes = [
        [1, 'Vacation Leave', 'Sec. 51, Rule XVI, Omnibus Rules Implementing E.O No. 292', 'vacation-leave', true],
        [2, 'Mandatory/Forced Leave', 'Sec. 51, Rule XVI, Omnibus Rules Implementing E.O No. 292', null, true],
        [3, 'Sick Leave', 'Sec. 51, Rule XVI, Omnibus Rules Implementing E.O No. 292', 'sick-leave', true],
        [4, 'Maternity Leave', 'R.A No. 11210/IRR issued by CSC, DOLE and SSS', null, false],
        [5, 'Paternity Leave', 'R.A No. 8187/CSC MC No. 71,s. 1998, as amended', null, false],
        [6, 'Special Privilege Leave', 'Sec. 21, Rule XVI, Omnibus Rules Implementing E.O No. 292', null, true],
        [7, 'Solo Parent Leave', 'R.A. No. 8972/CSC MC No. 8, s. 2004', null, false],
        [15, 'Wellness Leave', null, null, true],
        [8, 'Study Leave', 'Sec. 68, Rule XVI, Omnibus Rules Implementing E.O No. 292', 'study-leave', false],
        [9, '10-Day VAWC Leave', 'R.A No. 9262/CSC MO No. 15,s. 2005', null, false],
        [10, 'Rehabilitation Privilege', 'Sec. 55, Rule XVI, omnibus Rules Implementing E.O No. 292', null, false],
        [11, 'Special Leave Benefits for Women', 'R.A No. 9710/CSC MC No. 25,s. 2010', null, false],
        [12, 'Special Emergency (Calamity) Leave', 'CSC MC No. 2,s. 2012, as amended', null, false],
        [13, 'Adoption Leave', 'R.A. No. 8552', null, false],
        [14, 'Vacation Service Credit', 'R.A. No. 4670', null, true],
    ];
@endphp
<section class="content">
<div class="container-fluid dash">
    @include("leaves.page-head")

    <div class="row">
        @include("leaves.side-menu")
        <div class="col-lg-9">
        @if($guard == "web")
            @if(count($leaves) == 0)
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h5><i class="fas fa-flag-checkered" style="color: var(--cpsu-green-600);"></i>Set starting balance</h5>
                    </div>
                    <div class="dash-card-body">
                        <p class="lv-note">This employee has no leave credit records yet. Enter their current balances to start the ledger; later credits and deductions are added from here.</p>
                        <form class="dtr-form" action="{{ route('leavesCreate') }}" method="POST">
                            @csrf
                            <input type="hidden" name="empid" value="{{ $employee->id }}">
                            <div class="row">
                                <div class="col-md-4 col-sm-6 dtr-field">
                                    <label class="dtr-label" for="start-sl">Sick Leave</label>
                                    <input class="form-control" id="start-sl" type="number" name="sl" step="0.001" min="0" max="{{ (count($leaves) == 0) ? '' : 30 }}" placeholder="0.000" required>
                                </div>
                                <div class="col-md-4 col-sm-6 dtr-field">
                                    <label class="dtr-label" for="start-vl">Vacation Leave</label>
                                    <input class="form-control" id="start-vl" type="number" name="vl" step="0.001" min="0" placeholder="0.000" required>
                                </div>
                                <div class="col-md-8 dtr-field">
                                    <label class="dtr-label" for="start-remarks">Remarks <span class="font-weight-normal">(optional)</span></label>
                                    <textarea class="form-control" id="start-remarks" name="remarks" rows="3" style="height: auto;"></textarea>
                                </div>
                            </div>
                            <button type="submit" name="btn-submit" class="dtr-generate">
                                <i class="fas fa-save mr-1"></i> Save starting balance
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="dash-card">
                    <div class="dash-card-header flex-wrap">
                        <h5><i class="fas fa-book" style="color: var(--cpsu-green-600);"></i>Credit ledger</h5>
                        <div class="lv-actions">
                            <button type="button" class="lv-btn" data-toggle="modal" data-target="#leaveModalDeduct">
                                <i class="fas fa-minus"></i> Deduct
                            </button>
                            <button type="button" class="lv-btn is-primary" data-toggle="modal" data-target="#leaveModal">
                                <i class="fas fa-plus"></i> Add credits
                            </button>
                        </div>
                    </div>
                    <div class="dash-card-body">
                        <div class="table-responsive">
                            <table class="table table-hover lv-table" id="example3">
                                <thead>
                                    <tr>
                                        <th class="text-center">SL</th>
                                        <th class="text-center">VL</th>
                                        <th>For the month of</th>
                                        <th>Remarks</th>
                                        <th>Recorded</th>
                                        <th class="text-center">Entry</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($leaves as $leave)
                                    @php
                                        $date = ($leave->created_at) ? \Carbon\Carbon::parse($leave->created_at)->format('F d, Y') : '';
                                        $isDeduction = $leave->stat == 1 && $leave->days == 0;
                                    @endphp
                                        <tr id="tr-{{ $leave->id }}">
                                            <td class="text-center lv-num">{{ $leave->earn_sl }}</td>
                                            <td class="text-center lv-num">{{ $leave->earn_vl }}</td>
                                            <td>{{ \Carbon\Carbon::parse($leave->date)->format('F Y') }}</td>
                                            <td class="{{ $leave->remarks ? '' : 'lv-muted' }}">{{ $leave->remarks ?: '—' }}</td>
                                            <td style="white-space: nowrap;">{{ $date }}</td>
                                            <td class="text-center">
                                                @if($leave->stat == 0)
                                                    <span class="lv-pill is-start">Starting balance</span>
                                                @elseif($isDeduction)
                                                    <span class="lv-pill is-deducted">Deducted</span>
                                                @else
                                                    <span class="lv-pill is-added">Added</span>
                                                @endif
                                            </td>
                                            <td class="text-center" width="100">
                                                <span class="lv-row-actions">
                                                    <a href="#" class="lv-icon-btn leaves_edit" data-id="{{ $leave->id }}" title="Edit" aria-label="Edit" data-toggle="modal" data-target="{{ $isDeduction ? '#leaveModalDeductEdit' : '#leaveEditModal' }}">
                                                        <i class="fas fa-pen"></i>
                                                    </a>
                                                    @if($leave->stat == 0)
                                                        <button type="button" class="lv-icon-btn" value="{{ $leave->id }}" title="The starting balance can't be deleted" aria-label="Delete" disabled>
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @else
                                                        <button type="button" class="lv-icon-btn is-danger leaves_delete" value="{{ $leave->id }}" title="Delete" aria-label="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @endif
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        @else
            <form class="dtr-form lv-apply add-form" id="leaveApplyForm" action="{{ route('LeaveAppCreate') }}" method="POST">
                @csrf
                <input type="hidden" name="empid" value="{{ $employee->emp_ID }}">

                <div class="dash-card">
                    <div class="dash-card-header">
                        <h5><i class="fas fa-umbrella-beach" style="color: var(--cpsu-green-600);"></i>Type of leave</h5>
                        <span class="dash-card-hint d-none d-sm-inline">Dashed options aren't available for online filing</span>
                    </div>
                    <div class="dash-card-body">
                        <div class="lv-options" role="radiogroup" aria-label="Type of leave">
                            @foreach($leaveTypes as [$value, $name, $basis, $id, $available])
                                <label class="lv-option">
                                    <input class="leave-type" type="radio" value="{{ $value }}" name="leave_type" @if($id) id="{{ $id }}" @endif {{ $available ? '' : 'disabled' }} required>
                                    <span>
                                        <span class="lv-option-name">{{ $name }}</span>
                                        @if($basis)<span class="lv-option-basis">{{ $basis }}</span>@endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="dash-card">
                    <div class="dash-card-header">
                        <h5><i class="fas fa-clipboard-list" style="color: var(--cpsu-green-600);"></i>Details of leave</h5>
                        <span class="dash-card-hint d-none d-sm-inline">Opens up for the leave type you choose</span>
                    </div>
                    <div class="dash-card-body">
                        <div class="lv-details">
                            <div class="lv-detail-group">
                                <h6>Vacation / Special Privilege Leave</h6>
                                <label class="lv-choice">
                                    <input class="vacation-check" type="radio" value="1" name="leave_purpose" required disabled>
                                    Within the Philippines
                                </label>
                                <label class="lv-choice">
                                    <input class="vacation-check" type="radio" value="2" name="leave_purpose" id="abroad" required disabled>
                                    Abroad
                                    <input class="input-details vacation-leave" type="text" id="leaves_1" name="leave_detail[]" placeholder="Specify country" autocomplete="off">
                                </label>
                            </div>

                            <div class="lv-detail-group">
                                <h6>Sick Leave</h6>
                                <label class="lv-choice">
                                    <input class="sick-leave-detail" type="radio" value="3" name="leave_purpose" id="in-hospital" required disabled>
                                    In hospital (specify illness)
                                </label>
                                <label class="lv-choice">
                                    <input class="sick-leave-detail" type="radio" value="4" name="leave_purpose" id="out-patient" required disabled>
                                    Out patient
                                    <input class="input-details sick-leave" type="text" id="leaves_2" name="leave_detail[]" placeholder="Specify illness">
                                </label>
                            </div>

                            <div class="lv-detail-group">
                                <h6>Study Leave</h6>
                                <label class="lv-choice">
                                    <input class="leave-check" type="radio" value="5" name="leave_purpose" required disabled>
                                    Completion of Master's Degree
                                </label>
                                <label class="lv-choice">
                                    <input class="leave-check" type="radio" value="6" name="leave_purpose" required disabled>
                                    BAR/Board Examination Review
                                    <input class="input-details study-leave" type="text" id="leaves_3" name="leave_detail[]" autocomplete="off">
                                </label>
                            </div>

                            <div class="lv-detail-group">
                                <h6>Other purpose</h6>
                                <input type="radio" value="" name="leave_purpose" style="display: none;" checked id="monetizationdefault">
                                <div class="purpose-detail">
                                    <label class="lv-choice" for="monetization">
                                        <input type="radio" value="7" name="leave_purpose" id="monetization" disabled>
                                        Monetization of Leave Credits
                                    </label>
                                </div>
                                <div class="purpose-detail">
                                    <label class="lv-choice" for="terminal-leave">
                                        <input type="radio" value="8" name="leave_purpose" id="terminal-leave" disabled>
                                        Terminal Leave
                                        <input class="input-details" type="text" id="leaves_4" name="leave_detail[]" autocomplete="off">
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dash-card">
                    <div class="dash-card-header">
                        <h5><i class="far fa-calendar-alt" style="color: var(--cpsu-green-600);"></i>Inclusive dates</h5>
                    </div>
                    <div class="dash-card-body">
                        <div class="row">
                            <div class="col-md-6 dtr-field">
                                <label class="dtr-label" for="date_range">Dates</label>
                                <input type="text" id="date_range" name="date_range" class="form-control" placeholder="Pick the first and last day" required>
                            </div>
                            <div class="col-md-3 col-6 dtr-field">
                                <label class="dtr-label" for="day">Days applied</label>
                                <input type="text" id="day" name="days" class="form-control" autocomplete="off" placeholder="Weekdays only" readonly>
                            </div>
                            <div class="col-md-3 col-6 dtr-field">
                                <label class="dtr-label" for="date-filing">Date of filing</label>
                                <input type="date" id="date-filing" name="date_filing" class="form-control" value="{{ \Carbon\Carbon::now()->toDateString() }}" readonly>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="dtr-generate">
                                <i class="fas fa-paper-plane mr-1"></i> Submit application
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        @endif
        </div>
    </div>
</div>
</section>
@include("leaves.modal")
@endsection
