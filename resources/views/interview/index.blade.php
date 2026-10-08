@extends('layouts.master')

@section('body')
@php
    $isRankingAdmin = auth()->guard('web')->check() && in_array(auth()->guard('web')->user()->role, ['Administrator', 'HR Administrator'], true);
    $panelName = fn ($panel) => trim(($panel->employee->lname ?? '') . ', ' . ($panel->employee->fname ?? ''), ', ');
    $panelInitials = fn ($panel) => strtoupper(mb_substr($panel->employee->fname ?? '', 0, 1) . mb_substr($panel->employee->lname ?? '', 0, 1));
    $liveCount = $interviews->filter(fn ($interview) => $interview->activeApplication)->count();
@endphp
<div class="container-fluid dash interview-page">
    <div class="dtr-head">
        <div>
            <h1>Interview Assessment</h1>
            <p>Panel interviews per position: cast a candidate, follow the panel's ratings and open the ranking.</p>
        </div>
        <button type="button" class="lv-btn is-primary" data-toggle="modal" data-target="#addInterviewModal">
            <i class="fas fa-plus"></i> New interview
        </button>
    </div>

    <div class="dash-card">
        <div class="dash-card-header">
            <h5><i class="fas fa-comments" style="color: var(--cpsu-green-600);"></i>Interviews <span class="eli-count">{{ count($interviews) }}</span></h5>
            @if($liveCount > 0)
                <span class="lv-pill is-added set-state"><i class="fas fa-circle"></i> {{ $liveCount }} with a candidate on the floor</span>
            @endif
        </div>
        <div class="dash-card-body">
            @if($interviews->isEmpty())
                <div class="dtr-empty">
                    <div class="dtr-empty-icon"><i class="fas fa-comments"></i></div>
                    <h6>No interviews yet</h6>
                    <p>Create one from an ETE evaluation with <b>New interview</b>.</p>
                </div>
            @else
            <div class="table-responsive">
                <table id="example1" class="table table-hover lv-table ete-table">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Position</th>
                            <th>ETE source</th>
                            <th>Interview date</th>
                            <th>Panel</th>
                            <th>Active candidate</th>
                            <th>Ratings</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($interviews as $interview)
                            @php
                                $panelNames = $interview->panels->map($panelName)->filter()->values();
                                $shownPanel = $interview->panels->take(4);
                                $morePanel = $interview->panels->count() - $shownPanel->count();
                                $ratingTotal = $interview->ratings->count();
                                $ratingDone = $interview->ratings->whereNotNull('submitted_at')->count();
                                $ratingPct = $ratingTotal > 0 ? round($ratingDone / $ratingTotal * 100) : 0;
                                $active = $interview->activeApplication;
                            @endphp
                            <tr>
                                <td data-label="No." class="text-center lv-muted lv-num">{{ $loop->iteration }}</td>

                                <td data-label="Position" style="min-width: 200px;">
                                    <div class="um-name">{{ $interview->job->title ?? 'N/A' }}</div>
                                    @if($interview->job && $interview->job->plantilla_item_no)
                                        <div class="app-sub"><i class="fas fa-hashtag"></i>{{ $interview->job->plantilla_item_no }}</div>
                                    @endif
                                </td>

                                <td data-label="ETE source">
                                    <span class="um-page mb-0">ETE #{{ $interview->ete_id }}</span>
                                    @if($interview->eteEvaluation->office->office_name ?? false)
                                        <div class="app-sub">{{ $interview->eteEvaluation->office->office_name }}</div>
                                    @endif
                                </td>

                                <td data-label="Interview date" class="app-date" data-order="{{ optional($interview->interview_date)->format('Y-m-d H:i:s') }}">
                                    @if($interview->interview_date)
                                        <div>{{ $interview->interview_date->format('M d, Y') }}</div>
                                        <div class="lv-muted">{{ $interview->interview_date->format('h:i A') }}</div>
                                    @else
                                        <span class="lv-muted">Not set</span>
                                    @endif
                                </td>

                                <td data-label="Panel" data-order="{{ $interview->panels->count() }}">
                                    @if($interview->panels->isNotEmpty())
                                        <div class="ete-panel" title="{{ $panelNames->implode("\n") }}">
                                            <span class="ete-stack" aria-hidden="true">
                                                @foreach($shownPanel as $panel)
                                                    <span class="ete-face">{{ $panelInitials($panel) }}</span>
                                                @endforeach
                                                @if($morePanel > 0)
                                                    <span class="ete-face is-more">+{{ $morePanel }}</span>
                                                @endif
                                            </span>
                                            <span class="ete-panel-count">{{ $interview->panels->count() }} {{ $interview->panels->count() == 1 ? 'member' : 'members' }}</span>
                                            {{-- Names stay in the cell so the table search still finds them. --}}
                                            <span class="sr-only">{{ $panelNames->implode('; ') }}</span>
                                        </div>
                                    @else
                                        <span class="lv-muted">No panel</span>
                                    @endif
                                </td>

                                <td data-label="Active candidate">
                                    @if($active)
                                        <div class="iv-active">
                                            <span class="iv-live-dot" aria-hidden="true"></span>
                                            <div>
                                                <div class="app-position">{{ trim($active->first_name . ' ' . $active->last_name) }}</div>
                                                <div class="app-sub">{{ $active->app_number }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="lv-pill app-st-0">No cast candidate</span>
                                    @endif
                                </td>

                                <td data-label="Ratings" data-order="{{ $ratingDone }}" style="min-width: 130px;">
                                    <div class="iv-progress-label">
                                        <b>{{ $ratingDone }}</b> <span class="lv-muted">of {{ $ratingTotal }} submitted</span>
                                    </div>
                                    <div class="iv-progress {{ $ratingTotal > 0 && $ratingDone == $ratingTotal ? 'is-done' : '' }}" role="progressbar" aria-valuenow="{{ $ratingPct }}" aria-valuemin="0" aria-valuemax="100" aria-label="Ratings submitted">
                                        <span style="width: {{ $ratingPct }}%;"></span>
                                    </div>
                                </td>

                                <td data-label="Actions" class="text-center">
                                    <span class="lv-row-actions app-actions">
                                        <a href="{{ route('interviewEvaluationShow', $interview->id) }}" class="lv-icon-btn is-go" title="Manage" aria-label="Manage interview for {{ $interview->job->title ?? 'this position' }}">
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                        @if($isRankingAdmin)
                                            <a href="{{ route('interviewConsolidatedScreen', $interview->id) }}" target="_blank" rel="noopener" class="lv-icon-btn" title="Ranking" aria-label="Ranking">
                                                <i class="fas fa-trophy"></i>
                                            </a>
                                            <a href="{{ route('interviewSummaryRatingPdf', $interview->id) }}" target="_blank" rel="noopener" class="lv-icon-btn" title="Summary Rating of Applicants (PDF)" aria-label="Summary rating PDF">
                                                <i class="fas fa-file-pdf" style="color: #c0392b;"></i>
                                            </a>
                                        @endif
                                        <form action="{{ route('interviewEvaluationDelete', $interview->id) }}"
                                              method="POST"
                                              class="d-inline-block m-0 interview-delete-form"
                                              data-interview-title="{{ $interview->job->title ?? 'Interview Assessment' }}">
                                            @csrf
                                            <button type="submit" class="lv-icon-btn is-danger" title="Delete" aria-label="Delete interview">
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

<div class="modal fade ev-modal app-modal" id="addInterviewModal" tabindex="-1" role="dialog" aria-labelledby="addInterviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route('interviewEvaluationStore') }}" method="POST" class="dtr-form">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addInterviewLabel"><i class="fas fa-comments"></i>New interview assessment</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="job-form-group">Source</div>
                    <div class="form-row">
                        <div class="col-md-8 dtr-field">
                            <label class="dtr-label" for="ivEte">ETE evaluation</label>
                            <select name="ete_id" id="ivEte" class="form-control select2" required>
                                <option value="">Select ETE evaluation</option>
                                @foreach($etes as $ete)
                                    <option value="{{ $ete->id }}" {{ (string) old('ete_id') === (string) $ete->id ? 'selected' : '' }}>
                                        ETE #{{ $ete->id }} - {{ $ete->job->title ?? 'N/A' }}{{ $ete->job && $ete->job->plantilla_item_no ? ' - '.$ete->job->plantilla_item_no : '' }}{{ $ete->office ? ' - '.$ete->office->office_name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 dtr-field">
                            <label class="dtr-label" for="ivDate">Interview date</label>
                            <input type="datetime-local" name="interview_date" id="ivDate" class="form-control" value="{{ old('interview_date', now()->format('Y-m-d\TH:i')) }}">
                        </div>
                    </div>

                    <div class="job-form-group">Interview panel</div>
                    <div class="dtr-field">
                        <label class="dtr-label" for="ivPanels">Panel employees</label>
                        <select name="panels[]" id="ivPanels" class="form-control select2" multiple required>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ in_array((string) $employee->id, array_map('strval', (array) old('panels', [])), true) ? 'selected' : '' }}>{{ $employee->lname }}, {{ $employee->fname }} {{ $employee->mname }}</option>
                            @endforeach
                        </select>
                        <p class="pds-hint mt-1 mb-0">Each selected employee gets a rating form when a candidate is cast.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lv-btn" data-dismiss="modal">Close</button>
                    <button type="submit" class="lv-btn is-primary"><i class="fas fa-save"></i> Save interview</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Uses the page's jQuery, select2 and SweetAlert from masterScript (loaded after this section).
document.addEventListener('DOMContentLoaded', function () {
    $('#addInterviewModal').on('shown.bs.modal', function () {
        // select2's own container also has the .select2 class; only take the <select>s.
        $('#addInterviewModal select.select2').select2({
            dropdownParent: $('#addInterviewModal'),
            width: '100%',
            placeholder: 'Search...'
        });
    });

    $(document).on('submit', '.interview-delete-form', function (e) {
        e.preventDefault();
        const form = this;
        const title = $('<div>').text($(form).data('interview-title') || 'Interview Assessment').html();

        Swal.fire({
            title: 'Delete interview assessment?',
            html: `Delete "<strong>${title}</strong>" and all connected panel ratings? This action cannot be undone.`,
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
