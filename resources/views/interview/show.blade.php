@extends('layouts.master')

@section('body')
<style>
    .interview-manage .card { border:0; border-radius:18px; box-shadow:0 9px 30px rgba(15,23,42,.07); }
    .interview-manage-head { align-items:center; display:flex; flex-wrap:wrap; gap:12px; justify-content:space-between; padding:20px; }
    .interview-manage-head h4 { font-weight:800; margin:0; }
    .cast-pill { border-radius:999px; display:inline-block; font-size:.72rem; font-weight:800; padding:4px 9px; text-transform:uppercase; }
    .cast-pill.on { background:#dcfce7; color:#166534; }
    .cast-pill.off { background:#f1f5f9; color:#64748b; }
    .progress-mini { background:#edf2f7; border-radius:999px; height:8px; overflow:hidden; }
    .progress-mini span { background:#16a34a; display:block; height:100%; }
    .panel-link { border-radius:999px; margin:2px; }
    .panel-pill { border:1px solid; border-radius:999px; display:inline-block; font-size:.78rem; font-weight:700; margin:2px; padding:5px 10px; }
    .panel-pill.done { background:#f0fdf4; border-color:#16a34a; color:#166534; }
    .panel-pill.pending { background:#fef2f2; border-color:#dc2626; color:#991b1b; }
    .panel-assignment { align-items:center; display:inline-flex; gap:2px; margin:2px; }
    .panel-assignment .panel-link,
    .panel-assignment .panel-pill { margin:0; }
    .panel-remove { border-radius:999px; height:28px; line-height:1; padding:0; width:28px; }
    .action-cell { min-width:190px; width:190px; }
    .action-stack { align-items:center; display:flex; flex-direction:row; gap:6px; justify-content:center; }
    .action-stack form { margin:0; }
    .action-stack .btn { min-width:74px; }
    .action-stack .panel-add-btn { min-width:74px; }
</style>

<div class="container-fluid interview-manage">
    <div class="card mb-3">
        <div class="interview-manage-head">
            <div>
                <small class="text-muted">Interview Assessment from ETE #{{ $interview->ete_id }}</small>
                <h4>{{ $interview->job->title ?? 'Position' }}</h4>
                @if($interview->job && $interview->job->plantilla_item_no)
                    <div class="text-muted small">{{ $interview->job->plantilla_item_no }}</div>
                @endif
                <div class="text-muted small"><i class="fas fa-building mr-1"></i>{{ $interview->eteEvaluation->office->office_name ?? 'Office not set' }}</div>
            </div>
            <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
                @if(auth()->guard('web')->check() && in_array(auth()->guard('web')->user()->role, ['Administrator', 'HR Administrator'], true))
                    <a href="{{ route('interviewConsolidatedScreen', $interview->id) }}" target="_blank" class="btn btn-warning">
                        <i class="fas fa-ranking-star"></i> Ranking
                    </a>
                    <a href="{{ route('interviewSummaryRatingPdf', $interview->id) }}" target="_blank" class="btn btn-danger">
                        <i class="fas fa-file-pdf"></i> Summary Rating
                    </a>
                @endif
                <a href="{{ route('interviewEvaluationList') }}" class="btn btn-light border"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <div class="border-top p-3">
            <strong>Interview Panel</strong>
            <div class="mt-2">
                @foreach($interview->panels as $panel)
                    <span class="badge badge-info p-2 mb-1">{{ $panel->employee->lname ?? '' }}, {{ $panel->employee->fname ?? '' }}</span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="font-weight-bold mb-0">Candidates</h5>
                    <small class="text-muted">Scores and report actions are available directly in each row.</small>
                </div>
                <span class="badge badge-light border p-2">{{ $eligibleApplicants->count() }} applicants</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Applicant</th>
                            <th>Contact</th>
                            <th class="text-center">Cast Status</th>
                            <th class="text-center">Panel Progress</th>
                            <th>Interview Panel</th>
                            <th class="text-center action-cell">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $orderedEligibleApplicants = $eligibleApplicants->sortBy(function ($applicant) {
                                return strtolower(trim(($applicant->last_name ?? '').' '.($applicant->first_name ?? '').' '.($applicant->middle_name ?? '')));
                            })->values();
                        @endphp

                        @forelse($orderedEligibleApplicants as $applicant)
                            @php
                                $row = $interview->applicants->firstWhere('application_id', $applicant->id);
                                $isCast = $row && $row->is_cast;
                                $ratings = $ratingsByApplication->get($applicant->id, collect());
                                $completedRatings = $completedRatingsByApplication->get($applicant->id, collect());
                                $assignedPanels = $panelEmployeesByApplication->get($applicant->id, collect());
                                $assignedPanelIds = $assignedPanels->pluck('id');
                                $availablePanelEmployees = $employees->whereNotIn('id', $assignedPanelIds);
                                $submitted = $completedRatings->count();
                                $panelCount = max(1, $assignedPanels->count());
                                $percent = min(100, round(($submitted / $panelCount) * 100));
                                $middleInitial = trim((string) $applicant->middle_name) !== ''
                                    ? strtoupper(substr(trim($applicant->middle_name), 0, 1)).'.'
                                    : '';
                                $displayName = trim(($applicant->last_name ?? '').', '.($applicant->first_name ?? '').' '.$middleInitial);
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $displayName }}</strong>
                                    <small class="d-block text-muted">{{ $applicant->app_number }}</small>
                                </td>
                                <td>
                                    {{ $applicant->email }}
                                    <small class="d-block text-muted">{{ $applicant->mobile }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="cast-pill {{ $isCast ? 'on' : 'off' }}">{{ $isCast ? 'Cast' : 'Not cast' }}</span>
                                </td>
                                <td class="text-center">
                                    <strong>{{ $submitted }}/{{ $panelCount }}</strong>
                                    <div class="progress-mini mt-1"><span style="width: {{ $percent }}%"></span></div>
                                </td>
                                <td>
                                    @if($isCast)
                                        @foreach($assignedPanels as $panelEmployee)
                                            @php
                                                $panelRating = $ratings->firstWhere('panel_employee_id', $panelEmployee->id);
                                                $panelFinished = $panelRating && $completedRatings->contains('id', $panelRating->id);
                                            @endphp
                                            <div class="panel-assignment">
                                                <a href="{{ route('interviewRatingForm', ['id' => $interview->id, 'applicationId' => $applicant->id, 'panel_id' => $panelEmployee->id]) }}"
                                                   class="btn btn-sm {{ $panelFinished ? 'btn-outline-success' : 'btn-outline-danger' }} panel-link"
                                                   title="{{ $panelFinished ? 'Rating complete' : 'Not yet finished rating' }}">
                                                    <i class="fas {{ $panelFinished ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i> {{ $panelEmployee->lname ?? 'Panel' }}
                                                </a>
                                                @if($assignedPanels->count() > 1)
                                                    <button type="button"
                                                            class="btn btn-sm btn-light border text-danger panel-remove"
                                                            title="Remove panel for this applicant"
                                                            data-remove-panel-button
                                                            data-applicant-name="{{ e($displayName) }}"
                                                            data-panel-name="{{ e(trim(($panelEmployee->lname ?? '').', '.($panelEmployee->fname ?? ''))) }}">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <form method="POST" action="{{ route('interviewCandidatePanelRemove', [$interview->id, $applicant->id, $panelEmployee->id]) }}" class="d-none">
                                                        @csrf
                                                    </form>
                                                @endif
                                            </div>
                                        @endforeach
                                    @else
                                        @foreach($assignedPanels as $panelEmployee)
                                            @php
                                                $panelRating = $ratings->firstWhere('panel_employee_id', $panelEmployee->id);
                                                $panelFinished = $panelRating && $completedRatings->contains('id', $panelRating->id);
                                            @endphp
                                            <div class="panel-assignment">
                                                <span class="panel-pill {{ $panelFinished ? 'done' : 'pending' }}"
                                                      title="{{ $panelFinished ? 'Rating complete' : 'Not yet finished rating' }}">
                                                    <i class="fas {{ $panelFinished ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i> {{ $panelEmployee->lname ?? 'Panel' }}
                                                </span>
                                                @if($assignedPanels->count() > 1)
                                                    <button type="button"
                                                            class="btn btn-sm btn-light border text-danger panel-remove"
                                                            title="Remove panel for this applicant"
                                                            data-remove-panel-button
                                                            data-applicant-name="{{ e($displayName) }}"
                                                            data-panel-name="{{ e(trim(($panelEmployee->lname ?? '').', '.($panelEmployee->fname ?? ''))) }}">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <form method="POST" action="{{ route('interviewCandidatePanelRemove', [$interview->id, $applicant->id, $panelEmployee->id]) }}" class="d-none">
                                                        @csrf
                                                    </form>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="text-center action-cell">
                                    <div class="action-stack">
                                        @if($availablePanelEmployees->isNotEmpty())
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-success panel-add-btn"
                                                    title="Add panel for this applicant"
                                                    data-toggle="modal"
                                                    data-target="#addPanelModal{{ $applicant->id }}">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        @endif
                                        @if($isCast)
                                            <form method="POST" action="{{ route('interviewCandidateUncast', [$interview->id, $applicant->id]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye-slash"></i> Uncast</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('interviewCandidateCast', [$interview->id, $applicant->id]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-success"><i class="fas fa-bullhorn"></i> Cast</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">No applicants with status Qualified / Ready for Interview.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@foreach($orderedEligibleApplicants as $applicant)
    @php
        $assignedPanelEmployees = $panelEmployeesByApplication->get($applicant->id, collect());
        $assignedPanelIds = $assignedPanelEmployees->pluck('id');
        $availablePanelEmployees = $employees->whereNotIn('id', $assignedPanelIds);
    @endphp
    @if($availablePanelEmployees->isNotEmpty())
        <div class="modal fade" id="addPanelModal{{ $applicant->id }}" tabindex="-1" role="dialog" aria-labelledby="addPanelModalLabel{{ $applicant->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form method="POST" action="{{ route('interviewCandidatePanelAdd', [$interview->id, $applicant->id]) }}" class="modal-content">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="addPanelModalLabel{{ $applicant->id }}">
                            <i class="fas fa-user-plus"></i> Add Interview Panel
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <strong>{{ trim(($applicant->last_name ?? '').', '.($applicant->first_name ?? '')) }}</strong>
                            <small class="d-block text-muted">{{ $applicant->app_number }}</small>
                        </div>
                        <div class="form-group mb-0">
                            <label>Select Panel</label>
                            <select name="panel_employee_id" class="form-control panel-select" data-dropdown-parent="#addPanelModal{{ $applicant->id }}" required>
                                <option value="">Search panel employee</option>
                                @foreach($availablePanelEmployees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->lname }}, {{ $employee->fname }} {{ $employee->mname }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Panel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

@endforeach

<script>
(function waitForPanelSelect() {
    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
        window.setTimeout(waitForPanelSelect, 100);
        return;
    }

    const $ = window.jQuery;

    $('.modal').on('shown.bs.modal', function () {
        const $select = $(this).find('.panel-select');

        if (!$select.length || $select.hasClass('select2-hidden-accessible')) {
            return;
        }

        $select.select2({
            dropdownParent: $(this),
            width: '100%',
            placeholder: 'Search panel employee'
        });
    });

    $(document).on('click', '[data-remove-panel-button]', function () {
        const button = this;
        const form = button.nextElementSibling;
        const applicantName = button.dataset.applicantName || 'this applicant';
        const panelName = button.dataset.panelName || 'this panel member';

        if (!form) {
            return;
        }

        Swal.fire({
            title: 'Remove interview panel?',
            html: '<div class="text-left">' +
                '<p class="mb-2">This will remove <strong>' + panelName + '</strong> from <strong>' + applicantName + '</strong>.</p>' +
                '<p class="mb-0 text-danger font-weight-bold">Their rating for this applicant only will be deleted.</p>' +
                '</div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, remove panel',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
})();
</script>
@endsection
