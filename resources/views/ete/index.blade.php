@extends('layouts.master')

@section('body')
@php
    $canSeeConsolidated = auth()->guard('web')->check() && in_array(auth()->guard('web')->user()->role, ['Administrator', 'HR Administrator'], true);
    $totalApplicants = $eteEvaluations->sum(fn ($ete) => $ete->applicantRatings->count());
    $panelName = fn ($panel) => trim(($panel->employee->lname ?? '') . ', ' . ($panel->employee->fname ?? ''), ', ');
    $panelInitials = fn ($panel) => strtoupper(mb_substr($panel->employee->fname ?? '', 0, 1) . mb_substr($panel->employee->lname ?? '', 0, 1));
@endphp
<div class="container-fluid dash ete-list-page">
    <div class="dtr-head">
        <div>
            <h1>ETE Evaluations</h1>
            <p>Education, Training and Experience screening per position: applicants, report panel and consolidated ratings.</p>
        </div>
        <button type="button" class="lv-btn is-primary" data-toggle="modal" data-target="#add-ete-evaluation">
            <i class="fas fa-plus"></i> New ETE evaluation
        </button>
    </div>

    <div class="dash-card">
        <div class="dash-card-header">
            <h5><i class="fas fa-clipboard-check" style="color: var(--cpsu-green-600);"></i>Evaluations <span class="eli-count">{{ count($eteEvaluations) }}</span></h5>
            <span class="dash-card-hint">{{ number_format($totalApplicants) }} applicants under evaluation</span>
        </div>
        <div class="dash-card-body">
            @if($eteEvaluations->isEmpty())
                <div class="dtr-empty">
                    <div class="dtr-empty-icon"><i class="fas fa-clipboard-check"></i></div>
                    <h6>No ETE evaluations yet</h6>
                    <p>Create one with <b>New ETE evaluation</b>. Applicants marked <b>Reviewing</b> for the position are added automatically.</p>
                </div>
            @else
            <div class="table-responsive">
                <table id="example1" class="table table-hover lv-table ete-table">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Position</th>
                            <th>Office</th>
                            <th>Evaluation date</th>
                            <th>Experience years</th>
                            <th class="text-center">Applicants</th>
                            <th>Report panel</th>
                            <th>Created</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($eteEvaluations as $ete)
                            @php
                                $panelNames = $ete->evaluators->map($panelName)->filter()->values();
                                $shownPanel = $ete->evaluators->take(4);
                                $morePanel = $ete->evaluators->count() - $shownPanel->count();
                            @endphp
                            <tr>
                                <td data-label="No." class="text-center lv-muted lv-num">{{ $loop->iteration }}</td>

                                <td data-label="Position" style="min-width: 200px;">
                                    <div class="um-name">{{ $ete->job->title ?? 'N/A' }}</div>
                                    @if($ete->job && $ete->job->plantilla_item_no)
                                        <div class="app-sub"><i class="fas fa-hashtag"></i>{{ $ete->job->plantilla_item_no }}</div>
                                    @endif
                                </td>

                                <td data-label="Office">
                                    @if($ete->office)
                                        <span class="um-page mb-0">{{ $ete->office->office_name }}</span>
                                    @else
                                        <span class="lv-muted">Not set</span>
                                    @endif
                                </td>

                                <td data-label="Evaluation date" class="app-date" data-order="{{ optional($ete->evaluation_date)->format('Y-m-d H:i:s') }}">
                                    @if($ete->evaluation_date)
                                        <div>{{ $ete->evaluation_date->format('M d, Y') }}</div>
                                        <div class="lv-muted">{{ $ete->evaluation_date->format('h:i A') }}</div>
                                    @endif
                                </td>

                                <td data-label="Experience years">
                                    <span class="lv-pill app-st-0">{{ $ete->experience_years ?? 'N/A' }}</span>
                                </td>

                                <td data-label="Applicants" class="text-center">
                                    <span class="ete-count">{{ $ete->applicantRatings->count() }}</span>
                                </td>

                                <td data-label="Report panel" data-order="{{ $ete->evaluators->count() }}">
                                    @if($ete->evaluators->isNotEmpty())
                                        <div class="ete-panel" title="{{ $panelNames->implode("\n") }}">
                                            <span class="ete-stack" aria-hidden="true">
                                                @foreach($shownPanel as $panel)
                                                    <span class="ete-face">{{ $panelInitials($panel) }}</span>
                                                @endforeach
                                                @if($morePanel > 0)
                                                    <span class="ete-face is-more">+{{ $morePanel }}</span>
                                                @endif
                                            </span>
                                            <span class="ete-panel-count">{{ $ete->evaluators->count() }} {{ $ete->evaluators->count() == 1 ? 'evaluator' : 'evaluators' }}</span>
                                            {{-- Names stay in the cell so the table search still finds them. --}}
                                            <span class="sr-only">{{ $panelNames->implode('; ') }}</span>
                                        </div>
                                    @else
                                        <span class="lv-muted">No evaluator</span>
                                    @endif
                                </td>

                                <td data-label="Created" class="app-date" data-order="{{ optional($ete->created_at)->format('Y-m-d H:i:s') }}">
                                    @if($ete->created_at)
                                        <div>{{ $ete->created_at->format('M d, Y') }}</div>
                                        <div class="lv-muted">{{ $ete->created_at->format('h:i A') }}</div>
                                    @endif
                                </td>

                                <td data-label="Actions" class="text-center">
                                    <span class="lv-row-actions app-actions">
                                        <a href="{{ route('eteEvaluationShow', $ete->id) }}" class="lv-icon-btn is-go" title="Manage ETE" aria-label="Manage ETE for {{ $ete->job->title ?? 'this position' }}">
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                        @if($canSeeConsolidated)
                                            <a href="{{ route('eteConsolidatedScreen', $ete->id) }}" target="_blank" rel="noopener" class="lv-icon-btn" title="Consolidated screen" aria-label="Consolidated screen">
                                                <i class="fas fa-tv"></i>
                                            </a>
                                        @endif
                                        <form action="{{ route('eteEvaluationDelete', $ete->id) }}"
                                              method="POST"
                                              class="d-inline-block m-0 ete-delete-form"
                                              data-ete-title="{{ $ete->job->title ?? 'ETE Evaluation' }}">
                                            @csrf
                                            <button type="submit" class="lv-icon-btn is-danger" title="Delete ETE evaluation" aria-label="Delete ETE evaluation">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Add ETE Evaluation Modal --}}
<div class="modal fade ev-modal app-modal" id="add-ete-evaluation" tabindex="-1" role="dialog" aria-labelledby="addEteEvaluationLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route('eteEvaluationStore') }}" method="POST" class="dtr-form">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEteEvaluationLabel"><i class="fas fa-clipboard-check"></i>New ETE evaluation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="job-form-group">Position</div>
                    <div class="form-row">
                        <div class="col-md-6 dtr-field">
                            <label class="dtr-label" for="eteJid">Position</label>
                            <select name="jid" id="eteJid" class="form-control select2" required>
                                <option value="">Select position</option>
                                @foreach($jobs as $job)
                                    <option value="{{ $job->id }}" {{ (string) old('jid') === (string) $job->id ? 'selected' : '' }}>
                                        {{ $job->title }}{{ $job->plantilla_item_no ? ' - '.$job->plantilla_item_no : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 dtr-field">
                            <label class="dtr-label" for="eteOffice">Department / office</label>
                            <select name="off_id" id="eteOffice" class="form-control select2" required>
                                <option value="">Select department / office</option>
                                @foreach($offices as $office)
                                    <option value="{{ $office->id }}" {{ (string) old('off_id') === (string) $office->id ? 'selected' : '' }}>
                                        {{ $office->office_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="job-form-group">Schedule and scope</div>
                    <div class="form-row">
                        <div class="col-md-6 dtr-field">
                            <label class="dtr-label" for="eteDate">Evaluation date</label>
                            <input type="datetime-local" name="evaluation_date" id="eteDate" class="form-control" value="{{ old('evaluation_date', now()->format('Y-m-d\TH:i')) }}" required>
                        </div>
                        <div class="col-md-6 dtr-field">
                            <label class="dtr-label" for="eteYears">Experience years</label>
                            <input type="text" name="experience_years" id="eteYears" class="form-control" value="{{ old('experience_years') }}" placeholder="Example: 2021-2025" autocomplete="off" required>
                            <p class="pds-hint mt-1 mb-0">Use a range like <b>2021-2025</b>. These years appear in the admin rating form.</p>
                        </div>
                    </div>

                    <div class="job-form-group">Report panel</div>
                    <div class="dtr-field">
                        <label class="dtr-label" for="eteEvaluators">Report evaluators</label>
                        <select name="evaluators[]" id="eteEvaluators" class="form-control select2" multiple required>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ in_array((string) $employee->id, array_map('strval', (array) old('evaluators', [])), true) ? 'selected' : '' }}>
                                    {{ $employee->lname }}, {{ $employee->fname }} {{ $employee->mname }}
                                </option>
                            @endforeach
                        </select>
                        <p class="pds-hint mt-1 mb-0">Evaluators don't enter scores. Their names and signatures decide how many pages the official report has.</p>
                    </div>

                    <div class="ete-note">
                        <i class="fas fa-info-circle"></i>
                        <span>When saved, every applicant with status <b>Reviewing</b> for the selected position is added to this evaluation automatically.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lv-btn" data-dismiss="modal">Close</button>
                    <button type="submit" class="lv-btn is-primary"><i class="fas fa-save"></i> Create ETE</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Uses the page's jQuery, select2 and SweetAlert from masterScript (loaded after this section).
document.addEventListener('DOMContentLoaded', function () {
    $('#add-ete-evaluation').on('shown.bs.modal', function () {
        // select2's own container also has the .select2 class; only take the <select>s.
        $('#add-ete-evaluation select.select2').select2({
            dropdownParent: $('#add-ete-evaluation'),
            width: '100%',
            placeholder: 'Search...'
        });
    });

    // SweetAlert delete confirmation for ETE evaluations
    $(document).on('submit', '.ete-delete-form', function (e) {
        e.preventDefault();
        const form = this;
        const title = $('<div>').text($(form).data('ete-title') || 'ETE Evaluation').html();

        Swal.fire({
            title: 'Delete ETE evaluation?',
            html: `Delete "<strong>${title}</strong>" and all connected evaluator data? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
@endsection
