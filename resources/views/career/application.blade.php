@extends('layouts.master')

@section('body')
@php
    $current_route = request()->route()->getName();
    // [label, pill tone]
    $status_labels = [
        0 => 'Application Submitted',
        1 => 'Reviewing',
        2 => 'Qualified / Ready for Interview',
        3 => 'Disqualified',
        4 => 'Qualified yet not selected',
        5 => 'Top 5 / Psychological or Pre-Employment Test',
        6 => 'Not Hired',
        7 => 'Hired',
    ];
    $status_short = [
        0 => 'Submitted',
        1 => 'Reviewing',
        2 => 'Qualified',
        3 => 'Disqualified',
        4 => 'Not selected',
        5 => 'Top 5',
        6 => 'Not hired',
        7 => 'Hired',
    ];
    $status_tones = [
        0 => 'app-st-0',
        1 => 'is-info',
        2 => 'is-added',
        3 => 'is-deducted',
        4 => 'is-start',
        5 => 'app-st-5',
        6 => 'app-st-6',
        7 => 'app-st-7',
    ];
    // [field, label, icon]
    $documents = [
        ['pds', 'PDS', 'fa-file-alt', 'Personal Data Sheet'],
        ['wes', 'WES', 'fa-briefcase', 'Work Experience Sheet'],
        ['intent', 'Intent', 'fa-envelope-open-text', 'Intent Letter'],
        ['resume', 'Resume', 'fa-user', 'Resume'],
        ['tor', 'TOR', 'fa-graduation-cap', 'Transcript of Records'],
        ['coe', 'COE', 'fa-building', 'Certificate of Employment'],
        ['cert_training', 'COT', 'fa-certificate', 'Certificate of Training'],
    ];
    $positionLabel = fn ($job) => $job->title . (!empty($job->plantilla_item_no) ? ' - Plantilla No. ' . $job->plantilla_item_no : '');

    // Status chips: counts of what the filters currently return.
    $statusFilter = request('status');
    $hasStatusFilter = $statusFilter !== null && $statusFilter !== '';
    $statusCounts = $applications->countBy('status');
    $keepFilters = request()->only(['position_id', 'date_from', 'date_to']);
    $hasAnyFilter = request()->filled('position_id') || $hasStatusFilter || request()->filled('date_from') || request()->filled('date_to');
@endphp

<div class="container-fluid dash">
    <div class="dtr-head">
        <div>
            <h1>Applications</h1>
            <p>Applicants for posted positions: their documents, screening status and next steps.</p>
        </div>
        <button type="button" class="lv-btn is-primary" data-toggle="modal" data-target="#add-applicant">
            <i class="fas fa-user-plus"></i> Add applicant
        </button>
    </div>

    {{-- Filters --}}
    <div class="dash-card">
        <div class="dash-card-body">
            <form method="GET" action="{{ route('appList') }}" class="dtr-form application-filter">
                <div class="form-row align-items-end">
                    <div class="col-xl-4 col-md-6 dtr-field">
                        <label class="dtr-label" for="filter_position_id">Position</label>
                        <select name="position_id" id="filter_position_id" class="form-control select2">
                            <option value="">All Positions</option>
                            @foreach($jobs as $job)
                                <option value="{{ $job->id }}" {{ (string) request('position_id') === (string) $job->id ? 'selected' : '' }}>{{ $positionLabel($job) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6 dtr-field">
                        <label class="dtr-label" for="filter_status">Status</label>
                        <select name="status" id="filter_status" class="form-control">
                            <option value="">All statuses</option>
                            @foreach($status_labels as $value => $label)
                                <option value="{{ $value }}" {{ (string) request('status') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-sm-6 dtr-field">
                        <label class="dtr-label" for="filter_date_from">Applied from</label>
                        <input type="date" name="date_from" id="filter_date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-xl-2 col-sm-6 dtr-field">
                        <label class="dtr-label" for="filter_date_to">Applied to</label>
                        <input type="date" name="date_to" id="filter_date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-xl-1 col-12 dtr-field">
                        <button type="submit" class="dtr-generate w-100 px-2" title="Apply filter">
                            <i class="fas fa-filter mr-1"></i> Filter
                        </button>
                    </div>
                </div>
                <div class="app-filter-foot">
                    <div class="app-chips" aria-label="Status">
                        <a href="{{ route('appList', $keepFilters) }}" class="app-chip {{ $hasStatusFilter ? '' : 'is-active' }}">
                            All @unless($hasStatusFilter)<b>{{ count($applications) }}</b>@endunless
                        </a>
                        @foreach($status_short as $value => $label)
                            @php $isActive = $hasStatusFilter && (string) $statusFilter === (string) $value; @endphp
                            @continue(!$isActive && !$statusCounts->has($value))
                            <a href="{{ route('appList', array_merge($keepFilters, ['status' => $value])) }}" class="app-chip {{ $isActive ? 'is-active' : '' }}" title="{{ $status_labels[$value] }}">
                                <span class="app-dot {{ $status_tones[$value] }}"></span>{{ $label }} <b>{{ $statusCounts->get($value, 0) }}</b>
                            </a>
                        @endforeach
                    </div>
                    <div class="d-flex align-items-center" style="gap: 8px;">
                        @if($hasAnyFilter)
                            <a href="{{ route('appList') }}" class="dash-card-hint">Clear filters</a>
                        @endif
                        <button type="submit" class="lv-btn" formaction="{{ route('applicationReport') }}" formtarget="_blank" title="Generate report">
                            <i class="fas fa-file-pdf" style="color: #c0392b;"></i> Report
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- List --}}
    <div class="dash-card">
        <div class="dash-card-header">
            <h5><i class="fas fa-users" style="color: var(--cpsu-green-600);"></i>Applicants <span class="eli-count">{{ count($applications) }}</span></h5>
            <span class="dash-card-hint">Set a control number to unlock an applicant's files</span>
        </div>
        <div class="dash-card-body">
            <div class="table-responsive">
                <table id="example1" class="table table-hover lv-table app-table">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Applicant</th>
                            <th>Position</th>
                            <th>Contact</th>
                            <th>Documents</th>
                            <th>Applied</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($applications as $app)
                            @php
                                $fullName = trim(preg_replace('/\s+/', ' ', "{$app->first_name} {$app->middle_name} {$app->last_name}"));
                                $initials = strtoupper(mb_substr($app->first_name ?? '', 0, 1) . mb_substr($app->last_name ?? '', 0, 1));
                                $editPayload = [
                                    'id'          => $app->id,
                                    'jid'         => $app->jid,
                                    'first_name'  => $app->first_name,
                                    'middle_name' => $app->middle_name,
                                    'last_name'   => $app->last_name,
                                    'age'         => $app->age,
                                    'sex'         => $app->sex,
                                    'mobile'      => $app->mobile,
                                    'email'       => $app->email,
                                    'address'     => $app->address,
                                    'education'   => $app->education,
                                    'eligibility' => $app->eligibility,
                                    'created_at'  => optional($app->created_at)->format('Y-m-d\TH:i'),
                                ];

                                // Edit is only for manually-added applicants — those with
                                // no uploaded documents at all. Once any file exists, hide it.
                                $hasNoFiles = empty($app->pds)
                                    && empty($app->wes)
                                    && empty($app->intent)
                                    && empty($app->resume)
                                    && empty($app->tor)
                                    && empty($app->coe)
                                    && empty($app->cert_training);
                            @endphp
                            <tr id="tr-{{ $app->id }}">
                                <td class="text-center lv-muted lv-num">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="emp-person" style="min-width: 210px;">
                                        <span class="emp-initials" aria-hidden="true">{{ $initials }}</span>
                                        <div>
                                            <div class="um-name">{{ $fullName }}</div>
                                            <div class="app-ids">
                                                <span title="Application no.">{{ $app->app_number }}</span>
                                                @if($app->ctrl_no)
                                                    <span class="app-ctrl" title="Control no."><i class="fas fa-key"></i>{{ $app->ctrl_no }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td style="min-width: 180px;">
                                    <div class="app-position">{{ $app->position }}</div>
                                    @if(!empty($app->plantilla_item_no))
                                        <div class="app-sub"><i class="fas fa-hashtag"></i>{{ $app->plantilla_item_no }}</div>
                                    @endif
                                </td>
                                <td class="app-contact">
                                    @if($app->mobile)<div><i class="fas fa-phone-alt"></i>{{ $app->mobile }}</div>@endif
                                    @if($app->email)<div class="app-email"><i class="fas fa-envelope"></i>{{ $app->email }}</div>@endif
                                    @if($app->sex)<div class="app-sub">{{ ucfirst($app->sex) }}{{ $app->age ? ' · ' . $app->age . ' yrs' : '' }}</div>@endif
                                </td>
                                <td>
                                    @if (empty($app->ctrl_no))
                                        <button type="button"
                                                class="app-unlock set-ctrl"
                                                value="{{ $app->id }}"
                                                data-toggle="modal"
                                                data-target="#setCtrlModal"
                                                title="Set Control Number to unlock file access">
                                            <i class="fas fa-lock"></i> Set control no.
                                        </button>
                                    @elseif($hasNoFiles)
                                        <span class="lv-muted app-sub">No files uploaded</span>
                                    @else
                                        <div class="app-docs">
                                            @foreach($documents as [$field, $label, $icon, $title])
                                                @if(!empty($app->{$field}))
                                                    <a href="{{ asset('storage/' . $app->{$field}) }}" class="app-doc" target="_blank" rel="noopener" title="{{ $title }}">
                                                        <i class="fas {{ $icon }}"></i>{{ $label }}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="app-date" data-order="{{ optional($app->created_at)->format('Y-m-d H:i:s') }}">
                                    <div>{{ $app->created_at->format('M d, Y') }}</div>
                                    <div class="lv-muted">{{ $app->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="lv-pill {{ $status_tones[$app->status] ?? 'app-st-0' }}" title="{{ $status_labels[$app->status] ?? 'Unknown' }}">
                                        {{ $status_short[$app->status] ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="lv-row-actions app-actions">
                                        @if($hasNoFiles)
                                            <button type="button"
                                                    class="lv-icon-btn edit-applicant"
                                                    data-toggle="modal"
                                                    data-target="#edit-applicant"
                                                    data-application='@json($editPayload)'
                                                    title="Edit applicant details"
                                                    aria-label="Edit {{ $fullName }}">
                                                <i class="fas fa-user-edit"></i>
                                            </button>
                                        @endif
                                        @if($app->ctrl_no)
                                            <button type="button"
                                                    class="lv-icon-btn set-ctrl"
                                                    value="{{ $app->id }}"
                                                    data-ctrl-no="{{ $app->ctrl_no }}"
                                                    data-toggle="modal"
                                                    data-target="#setCtrlModal"
                                                    title="Edit control number"
                                                    aria-label="Edit control number of {{ $fullName }}">
                                                <i class="fas fa-key"></i>
                                            </button>
                                        @endif
                                        @if ($app->status == 1)
                                            <button type="button"
                                                    class="lv-icon-btn is-go q-btn"
                                                    data-app-id="{{ $app->id }}"
                                                    data-toggle="modal"
                                                    data-target="#qualifyModal"
                                                    title="Qualify and set interview">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button"
                                                    class="lv-icon-btn is-danger dq-btn"
                                                    data-app-id="{{ $app->id }}"
                                                    data-toggle="modal"
                                                    data-target="#dqModal"
                                                    title="Disqualify applicant">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @elseif ($app->status == 2)
                                            <form method="POST" action="{{ route('updateStatus') }}" class="d-inline m-0">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $app->id }}">
                                                <input type="hidden" name="status" value="4">
                                                <button type="submit" class="lv-icon-btn is-warn" title="Not selected for next stage">
                                                    <i class="fas fa-user-clock"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('updateStatus') }}" class="top5-confirm-form d-inline m-0">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $app->id }}">
                                                <input type="hidden" name="status" value="5">
                                                <button type="submit" class="lv-icon-btn is-go" title="Select for next stage (Top 5)">
                                                    <i class="fas fa-arrow-right"></i>
                                                </button>
                                            </form>
                                        @elseif ($app->status == 5)
                                            <form method="POST" action="{{ route('updateStatus') }}" class="d-inline m-0">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $app->id }}">
                                                <input type="hidden" name="status" value="6">
                                                <button type="submit" class="lv-icon-btn is-danger" title="Mark as not hired">
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('updateStatus') }}" class="d-inline m-0">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $app->id }}">
                                                <input type="hidden" name="status" value="7">
                                                <button type="submit" class="lv-icon-btn is-go" title="Mark as hired">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                            </form>
                                        @elseif(!$hasNoFiles && !$app->ctrl_no)
                                            <span class="lv-muted" title="No actions available">—</span>
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
</div>

{{-- Add Applicant Modal --}}
<div class="modal fade ev-modal app-modal" id="add-applicant" role="dialog" aria-labelledby="addApplicantLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route('applicationStore') }}" method="POST" class="dtr-form">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addApplicantLabel"><i class="fas fa-user-plus"></i>Add applicant</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="job-form-group">Application</div>
                    <div class="form-row">
                        <div class="col-md-8 dtr-field">
                            <label class="dtr-label">Position applied <span class="font-weight-normal">(select one or more)</span></label>
                            <select name="jid[]" class="form-control select2" multiple required>
                                @foreach($jobs as $job)
                                    <option value="{{ $job->id }}">{{ $positionLabel($job) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 dtr-field">
                            <label class="dtr-label">Date applied</label>
                            <input type="datetime-local" name="created_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </div>
                    </div>

                    <div class="job-form-group">Personal information</div>
                    <div class="form-row">
                        <div class="col-md-4 dtr-field">
                            <label class="dtr-label">First name</label>
                            <input type="text" name="first_name" class="form-control" autocomplete="off" required>
                        </div>
                        <div class="col-md-4 dtr-field">
                            <label class="dtr-label">Middle name <span class="font-weight-normal">(optional)</span></label>
                            <input type="text" name="middle_name" class="form-control" autocomplete="off">
                        </div>
                        <div class="col-md-4 dtr-field">
                            <label class="dtr-label">Last name</label>
                            <input type="text" name="last_name" class="form-control" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-2 col-4 dtr-field">
                            <label class="dtr-label">Age</label>
                            <input type="number" name="age" class="form-control" min="18" max="65" required>
                        </div>
                        <div class="col-md-3 col-8 dtr-field">
                            <label class="dtr-label">Sex</label>
                            <select name="sex" class="form-control" required>
                                <option value="">Select sex</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-3 dtr-field">
                            <label class="dtr-label">Mobile no.</label>
                            <input type="text" name="mobile" class="form-control" placeholder="09XXXXXXXXX" autocomplete="off" required>
                        </div>
                        <div class="col-md-4 dtr-field">
                            <label class="dtr-label">Email address</label>
                            <input type="email" name="email" class="form-control" placeholder="name@example.com" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="dtr-field">
                        <label class="dtr-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" required></textarea>
                    </div>

                    <div class="job-form-group">
                        Educational background
                        <button type="button" class="lv-btn app-add-row" id="addEducation"><i class="fas fa-plus"></i> Add</button>
                    </div>
                    <div id="educationWrapper">
                        <div class="form-row education-row">
                            <div class="col-md-6 dtr-field">
                                <label class="dtr-label">School / course / description</label>
                                <input type="text" name="education[]" class="form-control" required>
                            </div>
                            <div class="col-md-3 col-6 dtr-field">
                                <label class="dtr-label">Level</label>
                                <input type="text" name="elevel[]" class="form-control" placeholder="College, HS, etc." required>
                            </div>
                            <div class="col-md-2 col-4 dtr-field">
                                <label class="dtr-label">Year</label>
                                <input type="text" name="eyear[]" class="form-control" placeholder="2020" required>
                            </div>
                            <div class="col-md-1 col-2 dtr-field app-row-remove">
                                <button type="button" class="lv-icon-btn is-danger removeEducation" disabled title="Remove" aria-label="Remove"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="job-form-group">
                        Eligibility
                        <button type="button" class="lv-btn app-add-row" id="addEligibility"><i class="fas fa-plus"></i> Add</button>
                    </div>
                    <div id="eligibilityWrapper">
                        <div class="form-row eligibility-row">
                            <div class="col-11 dtr-field">
                                <input type="text" name="eligibility[]" class="form-control" placeholder="Civil Service, PRC, etc." aria-label="Eligibility">
                            </div>
                            <div class="col-1 dtr-field app-row-remove is-inline">
                                <button type="button" class="lv-icon-btn is-danger removeEligibility" disabled title="Remove" aria-label="Remove"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lv-btn" data-dismiss="modal">Close</button>
                    <button type="submit" class="lv-btn is-primary"><i class="fas fa-save"></i> Save applicant</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Edit Applicant Modal (details only) --}}
<div class="modal fade ev-modal app-modal" id="edit-applicant" role="dialog" aria-labelledby="editApplicantLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route('applicationUpdate') }}" method="POST" class="dtr-form">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editApplicantLabel"><i class="fas fa-user-edit"></i>Edit applicant</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_app_id">

                    <div class="job-form-group">Application</div>
                    <div class="form-row">
                        <div class="col-md-8 dtr-field">
                            <label class="dtr-label" for="edit_jid">Position applied</label>
                            <select name="jid" id="edit_jid" class="form-control select2-edit" required>
                                <option value="">Select position</option>
                                @foreach($jobs as $job)
                                    <option value="{{ $job->id }}">{{ $positionLabel($job) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 dtr-field">
                            <label class="dtr-label" for="edit_created_at">Date applied</label>
                            <input type="datetime-local" name="created_at" id="edit_created_at" class="form-control" required>
                        </div>
                    </div>

                    <div class="job-form-group">Personal information</div>
                    <div class="form-row">
                        <div class="col-md-4 dtr-field">
                            <label class="dtr-label" for="edit_first_name">First name</label>
                            <input type="text" name="first_name" id="edit_first_name" class="form-control" autocomplete="off" required>
                        </div>
                        <div class="col-md-4 dtr-field">
                            <label class="dtr-label" for="edit_middle_name">Middle name <span class="font-weight-normal">(optional)</span></label>
                            <input type="text" name="middle_name" id="edit_middle_name" class="form-control" autocomplete="off">
                        </div>
                        <div class="col-md-4 dtr-field">
                            <label class="dtr-label" for="edit_last_name">Last name</label>
                            <input type="text" name="last_name" id="edit_last_name" class="form-control" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-2 col-4 dtr-field">
                            <label class="dtr-label" for="edit_age">Age</label>
                            <input type="number" name="age" id="edit_age" class="form-control" min="18" max="65" required>
                        </div>
                        <div class="col-md-3 col-8 dtr-field">
                            <label class="dtr-label" for="edit_sex">Sex</label>
                            <select name="sex" id="edit_sex" class="form-control" required>
                                <option value="">Select sex</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-3 dtr-field">
                            <label class="dtr-label" for="edit_mobile">Mobile no.</label>
                            <input type="text" name="mobile" id="edit_mobile" class="form-control" autocomplete="off" required>
                        </div>
                        <div class="col-md-4 dtr-field">
                            <label class="dtr-label" for="edit_email">Email address</label>
                            <input type="email" name="email" id="edit_email" class="form-control" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="dtr-field">
                        <label class="dtr-label" for="edit_address">Address</label>
                        <textarea name="address" id="edit_address" class="form-control" rows="2" required></textarea>
                    </div>

                    <div class="job-form-group">Qualifications</div>
                    <div class="dtr-field">
                        <label class="dtr-label" for="edit_education">Educational background</label>
                        <textarea name="education" id="edit_education" class="form-control" rows="2" placeholder="e.g. BS Computer Science (College, 2020)" required></textarea>
                    </div>
                    <div class="dtr-field">
                        <label class="dtr-label" for="edit_eligibility">Eligibility <span class="font-weight-normal">(optional)</span></label>
                        <input type="text" name="eligibility" id="edit_eligibility" class="form-control" placeholder="Civil Service, PRC, etc.">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lv-btn" data-dismiss="modal">Close</button>
                    <button type="submit" class="lv-btn is-primary"><i class="fas fa-save"></i> Update applicant</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Set Control No. Modal --}}
<div class="modal fade ev-modal app-modal" id="setCtrlModal" tabindex="-1" role="dialog" aria-labelledby="setCtrlModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="ctrlForm" method="POST" action="{{ route('setCtrlNo') }}" class="dtr-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="setCtrlModalLabel"><i class="fas fa-key"></i>Control number</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body pb-3">
                    <input type="hidden" name="id" id="ctrlAppId">
                    <label class="dtr-label" for="ctrl_no">Control number</label>
                    <input type="text" name="ctrl_no" id="ctrl_no" class="form-control" placeholder="Enter control number" autocomplete="off" required>
                    <p class="pds-hint mt-2 mb-0">Setting a control number unlocks the applicant's uploaded files.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lv-btn" data-dismiss="modal">Close</button>
                    <button type="submit" class="lv-btn is-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Qualified (Interview Schedule) Modal --}}
<div class="modal fade ev-modal app-modal" id="qualifyModal" tabindex="-1" role="dialog" aria-labelledby="qualifyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('updateStatus') }}" class="dtr-form">
                @csrf
                <input type="hidden" name="id" id="qualifyAppId">
                <input type="hidden" name="status" value="2">
                <div class="modal-header">
                    <h5 class="modal-title" id="qualifyModalLabel"><i class="fas fa-calendar-check"></i>Qualify and set interview</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body pb-3">
                    <div class="dtr-field">
                        <label class="dtr-label" for="interview_datetime">Interview schedule <span class="text-danger">*</span></label>
                        <input type="datetime-local" id="interview_datetime" name="interview_datetime" class="form-control" required>
                        <p class="pds-hint mt-1 mb-0">Example: September 16, 2025, at 2:00 PM</p>
                    </div>
                    <div class="dtr-field mb-0">
                        <label class="dtr-label" for="venue">Venue <span class="text-danger">*</span></label>
                        <textarea id="venue" name="venue" class="form-control" rows="2" required>Conference Room, Admin Building/Bidding Room/Accreditation/ Mini Hotel</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lv-btn" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="lv-btn is-primary"><i class="fas fa-check"></i> Confirm and qualify</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Disqualification Reason Modal --}}
<div class="modal fade ev-modal app-modal" id="dqModal" tabindex="-1" role="dialog" aria-labelledby="dqModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('updateStatus') }}" class="dtr-form">
                @csrf
                <input type="hidden" name="id" id="dqAppId">
                <input type="hidden" name="status" value="3">
                <div class="modal-header">
                    <h5 class="modal-title" id="dqModalLabel"><i class="fas fa-times-circle"></i>Disqualify applicant</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body pb-3">
                    <label class="dtr-label" for="dqReason">Reason for disqualification <span class="text-danger">*</span></label>
                    <textarea name="reason" id="dqReason" class="form-control" rows="3" placeholder="Enter reason..." required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lv-btn" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="lv-btn is-danger"><i class="fas fa-check"></i> Confirm disqualification</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Delegated so the buttons keep working on every DataTables page.
    $(document).on('click', '.edit-applicant', function () {
        const app = JSON.parse(this.dataset.application || '{}');

        document.getElementById('edit_app_id').value = app.id || '';
        document.getElementById('edit_jid').value = app.jid || '';
        document.getElementById('edit_first_name').value = app.first_name || '';
        document.getElementById('edit_middle_name').value = app.middle_name || '';
        document.getElementById('edit_last_name').value = app.last_name || '';
        document.getElementById('edit_age').value = app.age || '';
        document.getElementById('edit_sex').value = app.sex || '';
        document.getElementById('edit_mobile').value = app.mobile || '';
        document.getElementById('edit_email').value = app.email || '';
        document.getElementById('edit_address').value = app.address || '';
        document.getElementById('edit_education').value = app.education || '';
        document.getElementById('edit_eligibility').value = app.eligibility || '';
        document.getElementById('edit_created_at').value = app.created_at || '';

        $('#edit_jid').trigger('change');
    });

    // Set Control No. (prefills the current number when editing)
    $(document).on('click', '.set-ctrl', function () {
        document.getElementById('ctrlAppId').value = this.value;
        document.getElementById('ctrl_no').value = this.dataset.ctrlNo || '';
    });

    // Qualified modal
    $(document).on('click', '.q-btn', function () {
        document.getElementById('qualifyAppId').value = this.dataset.appId;
    });

    // Disqualified modal
    $(document).on('click', '.dq-btn', function () {
        document.getElementById('dqAppId').value = this.dataset.appId;
    });
});
</script>
<script>
// Uses the page's single jQuery (loaded via masterScript). Wait for DOM + deferred
// scripts (jQuery, select2, bootstrap) so $ and .select2 are guaranteed available.
document.addEventListener('DOMContentLoaded', function () {
    $('#filter_position_id').select2({
        width: '100%',
        placeholder: 'All Positions'
    });

    $('#add-applicant').on('shown.bs.modal', function () {
        // select2's own container also has the .select2 class; only take the <select>.
        var $sel = $('#add-applicant select.select2');
        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.select2('destroy');
        }
        $sel.select2({
            dropdownParent: $('#add-applicant'),
            width: '100%',
            placeholder: 'Search Position',
            closeOnSelect: false
        });
    });

    $('#edit-applicant').on('shown.bs.modal', function () {
        var $sel = $('#edit-applicant .select2-edit');
        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.select2('destroy');
        }
        $sel.select2({
            dropdownParent: $('#edit-applicant'),
            width: '100%',
            placeholder: 'Search Position'
        }).val($('#edit_jid').val()).trigger('change.select2');
    });

    $('#addEducation').click(function () {
        $('#educationWrapper').append(`
            <div class="form-row education-row">
                <div class="col-md-6 dtr-field">
                    <label class="dtr-label">School / course / description</label>
                    <input type="text" name="education[]" class="form-control" required>
                </div>
                <div class="col-md-3 col-6 dtr-field">
                    <label class="dtr-label">Level</label>
                    <input type="text" name="elevel[]" class="form-control" placeholder="College, HS, etc." required>
                </div>
                <div class="col-md-2 col-4 dtr-field">
                    <label class="dtr-label">Year</label>
                    <input type="text" name="eyear[]" class="form-control" placeholder="2020" required>
                </div>
                <div class="col-md-1 col-2 dtr-field app-row-remove">
                    <button type="button" class="lv-icon-btn is-danger removeEducation" title="Remove" aria-label="Remove"><i class="fas fa-times"></i></button>
                </div>
            </div>
        `.trim());
    });

    $(document).on('click', '.removeEducation', function () {
        $(this).closest('.education-row').remove();
    });

    $('#addEligibility').click(function () {
        $('#eligibilityWrapper').append(`
            <div class="form-row eligibility-row">
                <div class="col-11 dtr-field">
                    <input type="text" name="eligibility[]" class="form-control" placeholder="Civil Service, PRC, etc." aria-label="Eligibility">
                </div>
                <div class="col-1 dtr-field app-row-remove is-inline">
                    <button type="button" class="lv-icon-btn is-danger removeEligibility" title="Remove" aria-label="Remove"><i class="fas fa-times"></i></button>
                </div>
            </div>
        `.trim());
    });

    $(document).on('click', '.removeEligibility', function () {
        $(this).closest('.eligibility-row').remove();
    });

    // Delegated so the confirm also works on rows from later DataTables pages.
    $(document).on('submit', '.top5-confirm-form', function (event) {
        var form = this;
        event.preventDefault();

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Confirm Next Stage?',
                text: 'Select this applicant for the Top 5 / Psychological or Pre-Employment Test stage?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#187744',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes',
                cancelButtonText: 'No'
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return;
        }

        if (confirm('Select this applicant for the Top 5 / Psychological or Pre-Employment Test stage?')) {
            form.submit();
        }
    });
});
</script>
@endsection
