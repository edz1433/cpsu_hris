{{--
    One CTO on the Status page. CTO has its own chain:
    employee (signed on filing) -> immediate supervisor / OIC -> HR (final).
    Expects $leaves (a leave_applications row with the joined names) and $guard.
--}}
@php
    $person = fn ($l, $f, $m, $s) => strtoupper(trim(preg_replace('/\s+/', ' ', "{$l}, {$f} " . ($s ? "{$s}. " : '') . ($m ? substr($m, 0, 1) . '.' : ''))));
    $when = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('F j, Y h:i A') : '';

    $signatory = $leaves->supervisorSignatory();
    $oicPerson = $signatory['is_oic'] ? \App\Models\Employee::find($signatory['id']) : null;
    $authUser = auth()->guard($guard)->user();

    $declinedBy = (int) $leaves->remarks_stat;           // 2 supervisor, 1 HR, 4 canceled after approval
    $status = (int) $leaves->status;
    $open = (int) $leaves->history === 1 && $declinedBy === 0;
    $supDone = in_array($status, [2, 4], true);
    $hrDone = $status === 4;

    $canSupervisor = $open && $status === 1 && $guard === 'employee' && (int) $authUser->id === $signatory['id'];
    $canHr = $open && $status === 2 && $guard === 'web' && $authUser->canAccessPage('leave');
    $canCancelApproved = $guard === 'web' && $authUser->canAccessPage('leave') && \App\Http\Controllers\LeaveApplicationController::isApprovedLeave($leaves);

    $applicant = isset($leaves->employee_lname)
        ? $person($leaves->employee_lname, $leaves->employee_fname, $leaves->employee_mname, $leaves->employee_suffix)
        : ($guard === 'web' ? $person($employee->lname, $employee->fname, $employee->mname, $employee->suffix) : 'ME');

    $icon = fn ($done, $declined) => $declined ? 'fa-ban bg-danger' : ($done ? 'fa-check bg-success' : 'fa-times bg-secondary');
@endphp
<div class="timeline timeline-inverse">
    <div class="time-label">
        <span class="bg-success"><i class="fas fa-user-circle"></i> {{ $applicant }} &emsp;</span>
    </div>

    {{-- Filed and signed by the employee --}}
    <div>
        <i class="fas fa-business-time bg-info"></i>
        <div class="timeline-item">
            <span class="time">{{ $when($leaves->date_filing) }}</span>
            <h3 class="timeline-header"><a href="#">CTO Application</a></h3>
            <div class="timeline-body">
                <button type="button" class="btn btn-danger btn-round btn-sm" style="float: right;" data-id="{{ $leaves->id }}" data-toggle="modal" data-target="#pdfModal" title="Preview CTO form">
                    <i class="fas fa-file-pdf"></i>
                </button>
                <span class="badge badge-success"><b>#{{ $leaves->transnum }}</b></span><br>
                @include('leaves.cto-summary')
                @if($guard === 'web' && $open && $status === 1)
                    <button type="button" class="btn btn-warning btn-sm cancelLeave float-right" value="{{ $leaves->id }}"><i class="fas fa-times"></i> Cancel</button>
                    <br>
                @endif
            </div>
        </div>
    </div>

    {{-- Immediate supervisor / OIC --}}
    <div>
        <i class="fas {{ $icon($supDone, $declinedBy === 2) }}"></i>
        <div class="timeline-item">
            <span class="time">{{ $when($leaves->sup_sdate) }}</span>
            <h3 class="timeline-header border-0">
                @if($oicPerson)
                    <a href="#">{{ $person($oicPerson->lname, $oicPerson->fname, $oicPerson->mname, $oicPerson->suffix) }}</a><br>
                    <span><i>OIC (for the Immediate Supervisor)</i></span>
                @else
                    <a href="#">{{ $person($leaves->supervisor_lname, $leaves->supervisor_fname, $leaves->supervisor_mname, $leaves->supervisor_suffix) }}</a><br>
                    <span><i>Immediate Supervisor</i></span>
                @endif
                @if($declinedBy === 2)
                    <div class="callout callout-danger" style="margin: 8px 0 0 0 !important; padding: 10px !important;">
                        <p class="mb-0"><b>Declined:</b> {{ $leaves->remarks_details }}</p>
                    </div>
                @endif
            </h3>
            @if($canSupervisor)
                <div class="timeline-footer mb-4">
                    <div class="float-right">
                        @if(empty($authUser->esign))
                            <a href="{{ url('pds/signature') }}">Click here to upload your e-signature</a>
                        @else
                            <button class="btn btn-success btn-sm cto-decide" data-id="{{ $leaves->id }}" data-step="supervisor" data-action="approve"><i class="fas fa-check"></i> Approve</button>
                            <button class="btn btn-danger btn-sm cto-decide" data-id="{{ $leaves->id }}" data-step="supervisor" data-action="decline"><i class="fas fa-ban"></i> Decline</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- HR, final --}}
    <div>
        <i class="fas {{ $icon($hrDone, $declinedBy === 1) }}"></i>
        <div class="timeline-item">
            <span class="time">{{ $when($leaves->hr_sdate) }}</span>
            <h3 class="timeline-header border-0">
                <a href="#">{{ $person($leaves->hr_lname, $leaves->hr_fname, $leaves->hr_mname, $leaves->hr_suffix) }}</a><br>
                <span><i>Head, HRMO</i></span>
                @if($declinedBy === 1)
                    <div class="callout callout-danger" style="margin: 8px 0 0 0 !important; padding: 10px !important;">
                        <p class="mb-0"><b>Declined:</b> {{ $leaves->remarks_details }}</p>
                    </div>
                @endif
            </h3>
            @if($canHr)
                <div class="timeline-footer mb-4">
                    <div class="float-right">
                        <button class="btn btn-success btn-sm cto-decide" data-id="{{ $leaves->id }}" data-step="hr" data-action="approve"><i class="fas fa-check"></i> Approve</button>
                        <button class="btn btn-danger btn-sm cto-decide" data-id="{{ $leaves->id }}" data-step="hr" data-action="decline"><i class="fas fa-ban"></i> Decline</button>
                    </div>
                </div>
            @elseif($canCancelApproved)
                <div class="timeline-footer mb-4">
                    <div class="float-right">
                        <button type="button" class="btn btn-warning btn-sm cancel-approved-leave" data-id="{{ $leaves->id }}"><i class="fas fa-ban"></i> Cancel approved CTO</button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div>
        <i class="fas {{ $hrDone && $declinedBy === 0 ? 'fa-check bg-success' : 'fa-times bg-secondary' }} mt-3"></i>
        <button type="button" data-id="{{ $leaves->id }}" data-toggle="modal" data-target="#pdfModal"
            class="btn {{ $hrDone ? 'btn-danger' : 'btn-secondary' }} btn-sm mt-3 ml-5 download">
            <i class="fas fa-file-pdf"></i> Preview
        </button>
    </div>
</div>
