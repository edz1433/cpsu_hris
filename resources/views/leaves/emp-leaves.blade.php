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
                                            <td class="{{ $leave->remarks ? '' : 'lv-muted' }}">{{ $leave->remarks ?: 'â€”' }}</td>
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
            @php
                // A CTO submit (or its validation error) comes back on the CTO tab.
                $activeTab = session('leave_tab', old('cto') ? 'cto' : 'leave');
                $cocBalance = (float) ($employee->coc_leave ?? 0);
                $ctoRows = old('cto') ?: [['date' => '', 'hours' => '']];
            @endphp

            <div data-lv-pane="leave" @if($activeTab === 'cto') hidden @endif>
            <form class="dtr-form lv-apply add-form" id="leaveApplyForm" action="{{ route('LeaveAppCreate') }}" method="POST">
                @csrf
                <input type="hidden" name="empid" value="{{ $employee->emp_ID }}">

                <div class="dash-card">
                    <div class="dash-card-header flex-wrap">
                        <h5><i class="fas fa-umbrella-beach" style="color: var(--cpsu-green-600);"></i>Type of leave</h5>
                        @include('leaves.form-tabs')
                    </div>
                    <div class="dash-card-body">
                        <p class="lv-note">Dashed options aren't available for online filing.</p>
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
                                <input type="text" id="date_range" name="date_range" class="form-control" placeholder="Pick the first and last day" autocomplete="off" readonly required>
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
            </div>

            {{-- Compensatory Time Off (CTO): one row per date with the hours used that day. --}}
            <div data-lv-pane="cto" @if($activeTab !== 'cto') hidden @endif>
            <form class="dtr-form lv-apply" id="ctoApplyForm" action="{{ route('ctoCreate') }}" method="POST">
                @csrf
                @php $fmtHours = fn ($h) => \App\Models\LeaveApplication::formatHours($h); @endphp
                <div class="dash-card">
                    <div class="dash-card-header flex-wrap">
                        <h5><i class="fas fa-business-time" style="color: var(--cpsu-green-600);"></i>Compensatory Time Off</h5>
                        @include('leaves.form-tabs')
                    </div>

                    @if($cocBalance <= 0)
                        {{-- Nothing to file against: explain instead of showing a form that can't be sent. --}}
                        <div class="dtr-empty">
                            <div class="dtr-empty-icon"><i class="fas fa-business-time"></i></div>
                            <h6>No overtime credits yet</h6>
                            <p>HR adds your Compensatory Overtime Credits (COCs) once your overtime is approved.<br>
                               When you have hours, you can file a CTO here.</p>
                        </div>
                    @else
                    <div class="dash-card-body">
                        {{-- Balance at a glance --}}
                        <div class="cto-summary" id="cto-summary" data-balance="{{ $cocBalance }}">
                            <div class="cto-stat">
                                <span class="cto-stat-label">Available</span>
                                <span class="cto-stat-value">{{ $fmtHours($cocBalance) }}<small>hrs</small></span>
                            </div>
                            <div class="cto-stat">
                                <span class="cto-stat-label">This request</span>
                                <span class="cto-stat-value"><span id="cto-total">0</span><small>hrs</small></span>
                            </div>
                            <div class="cto-stat">
                                <span class="cto-stat-label">Left after</span>
                                <span class="cto-stat-value" id="cto-remaining-wrap"><span id="cto-remaining">{{ $fmtHours($cocBalance) }}</span><small>hrs</small></span>
                            </div>
                            <div class="cto-meter" aria-hidden="true"><span id="cto-meter-bar"></span></div>
                        </div>

                        <p class="cto-help">
                            <i class="fas fa-info-circle"></i>
                            Add each day you'll take off and the hours you'll use that day (up to 10).
                            Your e-signature is attached when you submit. Your immediate supervisor approves it first, then HR;
                            the hours are deducted on HR's approval.
                        </p>
                        @if(empty($employee->esign))
                            <div class="cto-notice mb-3">
                                <i class="fas fa-signature"></i>
                                <span>You need an e-signature before you can file a CTO. <a href="{{ url('pds/signature') }}">Upload it here</a>, then come back.</span>
                            </div>
                        @endif

                        {{-- One row per date --}}
                        <div class="cto-head" aria-hidden="true">
                            <span>#</span><span>Date</span><span>Hours</span><span></span><span></span>
                        </div>
                        <div id="cto-rows">
                            @foreach($ctoRows as $i => $row)
                                <div class="cto-row">
                                    <span class="cto-num"></span>
                                    <input type="date" class="form-control cto-date" name="cto[{{ $i }}][date]" value="{{ $row['date'] ?? '' }}" aria-label="Date of CTO" required>
                                    <div class="cto-hours-wrap">
                                        <input type="number" class="form-control cto-hours" name="cto[{{ $i }}][hours]" value="{{ $row['hours'] ?? '' }}" min="0.5" max="10" step="0.5" placeholder="0" inputmode="decimal" aria-label="Hours" required>
                                        <span>hrs</span>
                                    </div>
                                    <div class="cto-quick" role="group" aria-label="Quick hours">
                                        <button type="button" class="cto-chip" data-hours="4">Half day</button>
                                        <button type="button" class="cto-chip" data-hours="8">Whole day</button>
                                    </div>
                                    <button type="button" class="lv-icon-btn is-danger cto-remove" title="Remove this date" aria-label="Remove this date"><i class="fas fa-times"></i></button>
                                    <div class="cto-row-error" aria-live="polite"></div>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" class="cto-add" id="cto-add">
                            <i class="fas fa-plus"></i> Add another date
                        </button>

                        <div class="cto-footer">
                            <div class="cto-notice" id="cto-warning" role="alert" hidden>
                                <i class="fas fa-exclamation-circle"></i> <span></span>
                            </div>
                            <button type="submit" class="dtr-generate ml-auto" id="cto-submit" data-no-esign="{{ empty($employee->esign) ? 1 : 0 }}">
                                <i class="fas fa-paper-plane mr-1"></i> Submit CTO
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </form>
            </div>

            <style>
                /* ---- CTO form ---- */
                .cto-summary {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 0;
                    border: 1px solid var(--cpsu-line);
                    border-radius: var(--cpsu-radius);
                    background: linear-gradient(135deg, var(--cpsu-green-50) 0%, #fff 85%);
                    overflow: hidden;
                    position: relative;
                    padding-bottom: 4px;
                }
                .cto-stat { padding: 14px 18px 12px; }
                .cto-stat + .cto-stat { border-left: 1px solid var(--cpsu-line); }
                .cto-stat-label {
                    display: block;
                    font-size: 11.5px;
                    font-weight: 600;
                    letter-spacing: .02em;
                    text-transform: uppercase;
                    color: var(--cpsu-ink-600);
                }
                .cto-stat-value {
                    display: block;
                    margin-top: 2px;
                    font-size: 24px;
                    font-weight: 700;
                    line-height: 1.2;
                    color: var(--cpsu-ink-900);
                    font-variant-numeric: tabular-nums;
                    transition: color .15s ease;
                }
                .cto-stat-value small { margin-left: 4px; font-size: 12px; font-weight: 600; color: var(--cpsu-ink-400); }
                .cto-stat-value.is-over { color: #a8322a; }
                .cto-meter { position: absolute; left: 0; right: 0; bottom: 0; height: 4px; background: var(--cpsu-green-100); }
                .cto-meter span { display: block; height: 100%; width: 0; background: var(--cpsu-green-600); transition: width .2s ease, background-color .2s ease; }
                .cto-meter span.is-over { background: #d0453a; }

                .cto-help { margin: 14px 0 18px; font-size: 13px; color: var(--cpsu-ink-600); }
                .cto-help i { color: var(--cpsu-green-600); margin-right: 4px; }

                .cto-head, .cto-row {
                    display: grid;
                    grid-template-columns: 28px minmax(170px, 240px) 130px auto 34px;
                    align-items: center;
                    column-gap: 12px;
                }
                .cto-head {
                    padding: 0 0 6px;
                    font-size: 11.5px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: .02em;
                    color: var(--cpsu-ink-600);
                    border-bottom: 1px solid var(--cpsu-line);
                }
                #cto-rows { counter-reset: cto-row; }
                .cto-row { padding: 10px 0; border-bottom: 1px dashed var(--cpsu-line); row-gap: 4px; }
                .cto-num::before {
                    counter-increment: cto-row;
                    content: counter(cto-row);
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 24px;
                    height: 24px;
                    border-radius: 50%;
                    background: var(--cpsu-green-100);
                    color: var(--cpsu-green-700);
                    font-size: 12px;
                    font-weight: 700;
                }
                .cto-row.has-error .cto-num::before { background: #fde0dd; color: #a8322a; }
                .cto-hours-wrap { position: relative; }
                .cto-hours-wrap input { padding-right: 38px; text-align: right; font-variant-numeric: tabular-nums; }
                .cto-hours-wrap span {
                    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
                    font-size: 12px; color: var(--cpsu-ink-400); pointer-events: none;
                }
                .cto-hours-wrap input.is-invalid { padding-right: 38px; background-image: none; }
                .cto-quick { display: flex; gap: 6px; flex-wrap: wrap; }
                .cto-chip {
                    height: 28px;
                    padding: 0 10px;
                    border: 1px solid #d5dfd9;
                    border-radius: 999px;
                    background: #fff;
                    font-size: 12px;
                    font-weight: 600;
                    color: var(--cpsu-ink-600);
                    transition: background-color .15s ease, border-color .15s ease, color .15s ease;
                }
                .cto-chip:hover { border-color: var(--cpsu-green-600); color: var(--cpsu-green-700); background: var(--cpsu-green-50); }
                .cto-chip:focus-visible { outline: 2px solid var(--cpsu-green-600); outline-offset: 2px; }
                .cto-row .cto-remove:disabled { visibility: hidden; }
                .cto-row-error { grid-column: 2 / -1; font-size: 12px; color: #a8322a; }
                .cto-row-error:empty { display: none; }

                .cto-add {
                    display: inline-flex;
                    align-items: center;
                    gap: 7px;
                    margin-top: 12px;
                    padding: 6px 2px;
                    border: 0;
                    background: none;
                    font-size: 13px;
                    font-weight: 600;
                    color: var(--cpsu-green-700);
                }
                .cto-add:hover { text-decoration: underline; }

                .cto-footer {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    flex-wrap: wrap;
                    margin-top: 18px;
                    padding-top: 16px;
                    border-top: 1px solid var(--cpsu-line);
                }
                .cto-notice {
                    display: flex;
                    align-items: flex-start;
                    gap: 8px;
                    padding: 8px 12px;
                    border-radius: 8px;
                    background: #fdf0ee;
                    color: #a8322a;
                    font-size: 13px;
                    line-height: 1.4;
                }
                .cto-notice i { margin-top: 2px; }
                .cto-notice[hidden] { display: none; }
                #cto-submit:disabled { opacity: .45; cursor: not-allowed; }

                @media (max-width: 767.98px) {
                    .cto-summary { grid-template-columns: 1fr 1fr 1fr; }
                    .cto-stat { padding: 12px 12px 10px; }
                    .cto-stat-value { font-size: 20px; }
                    .cto-row { grid-template-columns: 28px 1fr 110px; }
                    .cto-head { display: none; }
                    .cto-row .cto-remove { grid-column: 3; grid-row: 2; justify-self: end; }
                    .cto-row .cto-quick { grid-column: 2 / 3; grid-row: 2; }
                    .cto-footer #cto-submit { width: 100%; }
                }
            </style>

            <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Leave Application / CTO Form tabs
                document.querySelectorAll('[data-lv-tab]').forEach(function (tab) {
                    tab.addEventListener('click', function (e) {
                        e.preventDefault();
                        var name = tab.getAttribute('data-lv-tab');
                        document.querySelectorAll('[data-lv-pane]').forEach(function (pane) {
                            pane.hidden = pane.getAttribute('data-lv-pane') !== name;
                        });
                        document.querySelectorAll('[data-lv-tab]').forEach(function (t) {
                            t.classList.toggle('is-active', t.getAttribute('data-lv-tab') === name);
                        });
                    });
                });

                var rows = document.getElementById('cto-rows');
                var form = document.getElementById('ctoApplyForm');
                if (!rows || !form) return;

                var balance = parseFloat(document.getElementById('cto-summary').dataset.balance) || 0;
                var next = rows.querySelectorAll('.cto-row').length;
                var fmt = function (n) { return String(Math.round(n * 100) / 100); };
                var touched = false; // stay quiet until the employee starts filling rows

                function total() {
                    var sum = 0;
                    rows.querySelectorAll('.cto-hours').forEach(function (input) {
                        sum += parseFloat(input.value) || 0;
                    });
                    return sum;
                }

                // Marks bad fields on their own row and returns the first problem
                // for the whole form, or null when it can be submitted.
                function problem() {
                    var seen = {};
                    var first = null;

                    rows.querySelectorAll('.cto-row').forEach(function (row) {
                        var dateInput = row.querySelector('.cto-date');
                        var hoursInput = row.querySelector('.cto-hours');
                        var h = parseFloat(hoursInput.value);
                        var badHours = hoursInput.value !== '' && (isNaN(h) || h < 0.5 || h > 10);
                        var dupDate = dateInput.value !== '' && seen[dateInput.value];
                        seen[dateInput.value] = true;

                        var message = badHours ? 'Use 0.5 to 10 hours for one day.'
                            : dupDate ? 'This date is already listed above.'
                            : '';
                        hoursInput.classList.toggle('is-invalid', badHours);
                        dateInput.classList.toggle('is-invalid', !!dupDate);
                        row.querySelector('.cto-row-error').textContent = message;
                        row.classList.toggle('has-error', !!message);
                        first = first || (message ? 'Fix the highlighted row above.' : null);
                    });

                    if (first) return first;
                    if (total() > balance) {
                        return 'That is ' + fmt(total() - balance) + ' hrs more than you have. Lower the hours or remove a date.';
                    }
                    var incomplete = Array.prototype.some.call(rows.querySelectorAll('.cto-date, .cto-hours'), function (i) { return i.value === ''; });
                    if (incomplete) return touched ? 'Fill in the date and hours on every row.' : '';
                    return null;
                }

                function refresh() {
                    var sum = total();
                    var remaining = balance - sum;
                    var over = remaining < 0;

                    document.getElementById('cto-total').textContent = fmt(sum);
                    document.getElementById('cto-remaining').textContent = fmt(Math.max(remaining, 0));
                    document.getElementById('cto-remaining-wrap').classList.toggle('is-over', over);
                    var bar = document.getElementById('cto-meter-bar');
                    bar.style.width = Math.min(balance > 0 ? sum / balance * 100 : 0, 100) + '%';
                    bar.classList.toggle('is-over', over);

                    var only = rows.querySelectorAll('.cto-row').length === 1;
                    rows.querySelectorAll('.cto-remove').forEach(function (btn) { btn.disabled = only; });

                    var message = problem();
                    var warning = document.getElementById('cto-warning');
                    warning.querySelector('span').textContent = message || '';
                    warning.hidden = !message;
                    // '' = incomplete but untouched: keep the button off without nagging.
                    var submit = document.getElementById('cto-submit');
                    submit.disabled = message !== null || submit.dataset.noEsign === '1';
                }

                document.getElementById('cto-add').addEventListener('click', function () {
                    var row = rows.querySelector('.cto-row').cloneNode(true);
                    row.classList.remove('has-error');
                    row.querySelector('.cto-row-error').textContent = '';
                    row.querySelectorAll('input').forEach(function (input) {
                        input.value = '';
                        input.classList.remove('is-invalid');
                        input.name = input.name.replace(/cto\[\d+\]/, 'cto[' + next + ']');
                    });
                    next++;
                    rows.appendChild(row);
                    row.querySelector('.cto-date').focus();
                    refresh();
                });

                rows.addEventListener('click', function (e) {
                    var chip = e.target.closest('.cto-chip');
                    if (chip) {
                        var input = chip.closest('.cto-row').querySelector('.cto-hours');
                        input.value = chip.dataset.hours;
                        touched = true;
                        refresh();
                        return;
                    }
                    var btn = e.target.closest('.cto-remove');
                    if (btn && rows.querySelectorAll('.cto-row').length > 1) {
                        btn.closest('.cto-row').remove();
                        refresh();
                    }
                });
                // Hours can't go above 10 or below 0: clamp as the employee types.
                rows.addEventListener('input', function (e) {
                    var input = e.target;
                    if (input.classList.contains('cto-hours') && input.value !== '') {
                        var h = parseFloat(input.value);
                        if (h > 10) input.value = 10;
                        else if (h < 0) input.value = '';
                    }
                    touched = true;
                    refresh();
                });
                rows.addEventListener('keydown', function (e) {
                    if (e.target.classList.contains('cto-hours') && ['-', '+', 'e', 'E'].indexOf(e.key) !== -1) {
                        e.preventDefault();
                    }
                });

                form.addEventListener('submit', function (e) {
                    touched = true;
                    var message = problem();

                    if (message !== null) {
                        refresh();
                        message = message || 'Fill in the date and hours on every row.';
                        e.preventDefault();
                        if (window.Swal) {
                            Swal.fire({ icon: 'error', title: 'Check your CTO', text: message });
                        } else {
                            alert(message);
                        }
                    }
                });

                refresh();
            });
            </script>
        @endif
        </div>
    </div>
</div>
</section>
@include("leaves.modal")
@endsection
