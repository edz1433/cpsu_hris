@extends('layouts.master')

@section('body')
<style>
    .ete-manage {
        --ete-green: #198754;
        --ete-soft: #f4f7f6;
    }
    .ete-manage .card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 8px 28px rgba(15, 23, 42, .07);
    }
    .ete-page-head,
    .ete-section-head,
    .ete-selected-head,
    .ete-cast-row {
        align-items: center;
        display: flex;
        gap: 12px;
        justify-content: space-between;
    }
    .ete-page-head {
        flex-wrap: wrap;
        padding: 18px 20px;
    }
    .ete-title {
        font-size: 1.15rem;
        font-weight: 700;
        margin: 0;
    }
    .ete-actions,
    .ete-evaluator-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .ete-panel-pill {
        background: #fff;
        border: 1px solid #b7dfc8;
        border-radius: 999px;
        color: #157347;
        display: inline-flex;
        padding: 7px 12px;
    }
    .ete-applicants {
        max-height: 610px;
        overflow-y: auto;
        padding: 10px;
    }
    .ete-applicant {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px !important;
        margin-bottom: 8px;
        padding: 13px 14px;
        text-align: left;
        transition: .15s ease;
        width: 100%;
    }
    .ete-applicant:hover {
        border-color: #86b99b;
        transform: translateY(-1px);
    }
    .ete-applicant.selected {
        background: #edf8f1;
        border-color: var(--ete-green);
        box-shadow: inset 4px 0 0 var(--ete-green);
    }
    .ete-applicant.is-live::after {
        background: var(--ete-green);
        border-radius: 999px;
        color: #fff;
        content: "LIVE";
        float: right;
        font-size: .68rem;
        font-weight: 700;
        padding: 3px 8px;
    }
    .ete-selected-head {
        background: linear-gradient(135deg, #198754, #2fa56b);
        border-radius: 16px 16px 0 0;
        color: #fff;
        padding: 18px 20px;
    }
    .ete-cast-row {
        background: var(--ete-soft);
        border: 1px solid #dfe7e2;
        border-radius: 12px;
        margin-bottom: 18px;
        padding: 14px;
    }
    .ete-toggle {
        border-radius: 999px;
        font-weight: 700;
        min-width: 150px;
        padding: 9px 16px;
    }
    .ete-metric {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        min-height: 92px;
        padding: 14px;
        text-align: center;
    }
    .ete-metric small {
        color: #6c757d;
        display: block;
        font-weight: 700;
        text-transform: uppercase;
    }
    .ete-metric strong {
        display: block;
        font-size: 1.65rem;
    }
    .ete-evaluator-row {
        align-items: center;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        margin-bottom: 8px;
        padding: 12px;
    }
    @media (max-width: 767.98px) {
        .ete-manage {
            padding-left: 8px;
            padding-right: 8px;
        }
        .ete-page-head,
        .ete-selected-head,
        .ete-cast-row,
        .ete-evaluator-row {
            align-items: stretch;
            flex-direction: column;
        }
        .ete-actions .btn,
        .ete-toggle {
            flex: 1 1 auto;
            width: 100%;
        }
        .ete-applicants {
            display: flex;
            gap: 8px;
            max-height: none;
            overflow-x: auto;
            padding-bottom: 12px;
        }
        .ete-applicant {
            flex: 0 0 82%;
            margin-bottom: 0;
        }
        .ete-evaluator-row .text-right {
            text-align: left !important;
        }
    }
</style>

<div class="container-fluid ete-manage">
    <div class="card mb-3">
        <div class="ete-page-head">
            <div>
                <div class="text-muted small">ETE Evaluation</div>
                <h1 class="ete-title">{{ $ete->job->title ?? 'Position' }}</h1>
            </div>
            <div class="ete-actions">
                <a href="{{ route('eteEvaluationList') }}" class="btn btn-light border">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <a href="{{ route('eteConsolidatedScreen', $ete->id) }}" target="_blank" class="btn btn-warning">
                    <i class="fas fa-chart-column"></i> Ranking
                </a>
            </div>
        </div>
        <div class="border-top p-3">
            <div class="font-weight-bold mb-2">Evaluator Rating Pages</div>
            <div class="ete-evaluator-list">
                @foreach($ete->evaluators as $panel)
                    <a href="{{ route('eteEvaluatorRate', [$ete->id, $panel->emp_id]) }}"
                       target="_blank" class="ete-panel-pill">
                        <i class="fas fa-user-check mr-2"></i>
                        {{ $panel->employee->lname ?? '' }}, {{ $panel->employee->fname ?? '' }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="ete-section-head border-bottom p-3">
                    <strong>Applicants</strong>
                    <span class="badge badge-light border">{{ $applicants->count() }}</span>
                </div>
                <div class="ete-applicants" id="applicant-list">
                    @forelse($applicants as $applicant)
                        <button type="button"
                                class="ete-applicant applicant-btn {{ (int) $ete->active_application_id === (int) $applicant->id ? 'is-live' : '' }}"
                                data-applicant="{{ $applicant->id }}">
                            <strong class="d-block">
                                {{ trim($applicant->first_name . ' ' . $applicant->middle_name . ' ' . $applicant->last_name) }}
                            </strong>
                            <small class="text-muted">{{ $applicant->app_number }}</small>
                        </button>
                    @empty
                        <div class="text-muted p-3">No applicants available.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-3">
            <div id="empty-rating" class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-hand-pointer fa-2x text-success mb-3"></i>
                    <h5>Select an applicant</h5>
                    <p class="text-muted mb-0">Selection only previews scores. Use the toggle to cast the applicant.</p>
                </div>
            </div>

            <div id="selected-consolidated" class="card d-none">
                <div class="ete-selected-head">
                    <div>
                        <small>Selected applicant</small>
                        <strong id="selected-name" class="d-block"></strong>
                        <span id="selected-app-number"></span>
                    </div>
                    <span id="selected-live-badge" class="badge badge-light d-none">Currently live</span>
                </div>
                <div class="card-body">
                    <div class="ete-cast-row">
                        <div>
                            <strong id="cast-status-title">Not cast</strong>
                            <div id="cast-status-text" class="text-muted small">
                                Evaluators cannot see this applicant yet.
                            </div>
                        </div>
                        <button type="button" id="cast-toggle" class="btn btn-success ete-toggle">
                            <i class="fas fa-tower-broadcast"></i> Cast Applicant
                        </button>
                    </div>

                    <a id="applicant-pdf-link" href="#" target="_blank" class="btn btn-outline-danger btn-block mb-3">
                        <i class="fas fa-file-pdf mr-1"></i> Generate Official ETE PDF
                    </a>

                    <div class="row mb-3">
                        <div class="col-6 col-md-3 mb-2"><div class="ete-metric"><small>Education</small><strong id="education-avg">0.00</strong></div></div>
                        <div class="col-6 col-md-3 mb-2"><div class="ete-metric"><small>Training</small><strong id="training-avg">0.00</strong></div></div>
                        <div class="col-6 col-md-3 mb-2"><div class="ete-metric"><small>Experience</small><strong id="experience-avg">0.00</strong></div></div>
                        <div class="col-6 col-md-3 mb-2"><div class="ete-metric"><small>Total</small><strong id="total-avg">0.00</strong></div></div>
                    </div>

                    <div class="ete-section-head mb-2">
                        <strong><i class="fas fa-users mr-1"></i> Evaluators</strong>
                        <span class="badge badge-light border" id="rating-progress">0 / 0 completed</span>
                    </div>
                    <div id="active-evaluator-buttons"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function () {
    const eteId = @json($ete->id);
    let selectedApplicantId = null;
    let activeApplicantId = @json($ete->active_application_id);
    let castRequestRunning = false;

    function markApplicants() {
        $('.applicant-btn').each(function () {
            const id = Number($(this).data('applicant'));
            $(this).toggleClass('selected', id === Number(selectedApplicantId));
            $(this).toggleClass('is-live', id === Number(activeApplicantId));
        });
    }

    function updateCastToggle() {
        const isActive = Number(selectedApplicantId) === Number(activeApplicantId);
        $('#selected-live-badge').toggleClass('d-none', !isActive);
        $('#cast-status-title').text(isActive ? 'Applicant is live' : 'Applicant is not live');
        $('#cast-status-text').text(isActive
            ? 'Assigned evaluators can now rate this applicant.'
            : 'Evaluators cannot see this applicant until you cast it.');
        $('#cast-toggle')
            .toggleClass('btn-success', !isActive)
            .toggleClass('btn-outline-danger', isActive)
            .html(isActive
                ? '<i class="fas fa-stop-circle"></i> Uncast Applicant'
                : '<i class="fas fa-tower-broadcast"></i> Cast Applicant');
    }

    function renderEvaluatorButtons(evaluators) {
        let html = '';
        $.each(evaluators || [], function (_, evaluator) {
            html += `
                <div class="ete-evaluator-row">
                    <div>
                        <strong>${evaluator.name || 'Evaluator'}</strong>
                        <div class="text-muted small">Total score: ${evaluator.total_score}</div>
                    </div>
                    <div class="text-right">
                        <span class="badge ${evaluator.completed ? 'badge-success' : 'badge-secondary'} mr-1">
                            ${evaluator.completed ? 'Rated' : 'Waiting'}
                        </span>
                        <a href="${evaluator.url}" target="_blank" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-arrow-up-right-from-square"></i> Open
                        </a>
                    </div>
                </div>`;
        });
        $('#active-evaluator-buttons').html(html || '<div class="text-muted">No evaluators assigned.</div>');
    }

    function loadSelectedApplicant() {
        if (!selectedApplicantId) return;

        $.get("{{ route('eteSelectedApplicantConsolidated', $ete->id) }}", {
            application_id: selectedApplicantId
        }).done(function (response) {
            if (!response.success) return;

            activeApplicantId = response.active_application_id;
            $('#selected-name').text(response.name);
            $('#selected-app-number').text(response.app_number);
            $('#education-avg').text(response.education_avg);
            $('#training-avg').text(response.training_avg);
            $('#experience-avg').text(response.experience_avg);
            $('#total-avg').text(response.total_avg);
            $('#rating-progress').text(response.completed_count + ' / ' + response.evaluator_count + ' completed');
            $('#applicant-pdf-link').attr(
                'href',
                @json(url('/ete/ete-evaluations/' . $ete->id . '/applicant')) + '/' + response.application_id + '/pdf'
            );
            renderEvaluatorButtons(response.evaluators);
            markApplicants();
            updateCastToggle();
            $('#empty-rating').addClass('d-none');
            $('#selected-consolidated').removeClass('d-none');
        });
    }

    $('.applicant-btn').on('click', function () {
        selectedApplicantId = Number($(this).data('applicant'));
        markApplicants();
        loadSelectedApplicant();
    });

    $('#cast-toggle').on('click', function () {
        if (!selectedApplicantId || castRequestRunning) return;
        castRequestRunning = true;
        const button = $(this);
        const action = Number(selectedApplicantId) === Number(activeApplicantId) ? 'uncast' : 'cast';

        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

        $.post("{{ route('eteSplashApplicant') }}", {
            _token: "{{ csrf_token() }}",
            ete_id: eteId,
            application_id: selectedApplicantId,
            action: action
        }).done(function (response) {
            activeApplicantId = response.active_application_id;
            markApplicants();
            updateCastToggle();
        }).fail(function () {
            alert('Unable to update the live applicant.');
        }).always(function () {
            castRequestRunning = false;
            button.prop('disabled', false);
            updateCastToggle();
        });
    });

    window.setInterval(function () {
        if (selectedApplicantId && !castRequestRunning) loadSelectedApplicant();
    }, 2000);
});
</script>
@endsection
