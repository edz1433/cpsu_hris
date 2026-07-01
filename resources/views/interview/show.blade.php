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
                            <th class="text-center">Action</th>
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
                                $submitted = $completedRatings->count();
                                $panelCount = max(1, $interview->panels->count());
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
                                    <strong>{{ $submitted }}/{{ $interview->panels->count() }}</strong>
                                    <div class="progress-mini mt-1"><span style="width: {{ $percent }}%"></span></div>
                                </td>
                                <td>
                                    @if($isCast)
                                        @foreach($interview->panels as $panel)
                                            @php
                                                $panelRating = $ratings->firstWhere('panel_employee_id', $panel->emp_id);
                                                $panelFinished = $panelRating && $completedRatings->contains('id', $panelRating->id);
                                            @endphp
                                            <a href="{{ route('interviewRatingForm', ['id' => $interview->id, 'applicationId' => $applicant->id, 'panel_id' => $panel->emp_id]) }}"
                                               class="btn btn-sm {{ $panelFinished ? 'btn-outline-success' : 'btn-outline-danger' }} panel-link"
                                               title="{{ $panelFinished ? 'Rating complete' : 'Not yet finished rating' }}">
                                                <i class="fas {{ $panelFinished ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i> {{ $panel->employee->lname ?? 'Panel' }}
                                            </a>
                                        @endforeach
                                    @else
                                        @foreach($interview->panels as $panel)
                                            @php
                                                $panelRating = $ratings->firstWhere('panel_employee_id', $panel->emp_id);
                                                $panelFinished = $panelRating && $completedRatings->contains('id', $panelRating->id);
                                            @endphp
                                            <span class="panel-pill {{ $panelFinished ? 'done' : 'pending' }}"
                                                  title="{{ $panelFinished ? 'Rating complete' : 'Not yet finished rating' }}">
                                                <i class="fas {{ $panelFinished ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i> {{ $panel->employee->lname ?? 'Panel' }}
                                            </span>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="text-center">
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
@endsection
