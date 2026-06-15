@extends('layouts.master')

@section('body')
<style>
    .ete-live-shell .card {
        border-radius: 8px;
    }

    .ete-live-shell .applicant-btn {
        border-left: 4px solid transparent;
        transition: background-color .15s ease, border-color .15s ease;
    }

    .ete-live-shell .applicant-btn.active,
    .ete-live-shell .applicant-btn.is-live {
        background: #e9f7ef;
        border-left-color: #28a745;
        color: #155724;
    }

    .ete-metric {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 14px;
        background: #fff;
        min-height: 94px;
    }

    .ete-metric small {
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
    }

    .ete-metric strong {
        display: block;
        font-size: 1.8rem;
        line-height: 1.1;
    }

    .ete-evaluator-link {
        align-items: center;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 12px;
        margin-bottom: 8px;
        background: #fff;
    }
</style>

<div class="container-fluid ete-live-shell">

    <div class="card card-info card-outline">

        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-clipboard-check"></i>
                ETE Evaluation - {{ $ete->job->title ?? '' }}
            </h3>

            <a href="{{ route('eteConsolidatedScreen', $ete->id) }}"
               target="_blank"
               class="btn btn-warning btn-sm float-right ml-1">
                <i class="fas fa-tv"></i> Consolidated Ranking
            </a>

            <a href="{{ route('eteEvaluationList') }}" class="btn btn-secondary btn-sm float-right">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="card-body">

            {{-- Evaluator Links --}}
            <div class="mb-3">
                <strong>Evaluator Rating Pages:</strong>
                <br>

                @foreach($ete->evaluators as $panel)
                    <a href="{{ route('eteEvaluatorRate', [$ete->id, $panel->emp_id]) }}"
                       target="_blank"
                       class="btn btn-sm btn-outline-success mt-1">
                        <i class="fas fa-user-check"></i>
                        {{ $panel->employee->lname ?? '' }},
                        {{ $panel->employee->fname ?? '' }}
                    </a>
                @endforeach
            </div>

            <div class="row">

                {{-- LEFT SIDE --}}
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <strong>Applicants</strong>
                        </div>

                        <div class="list-group list-group-flush" id="applicant-list">
                            @foreach($applicants as $applicant)
                                <button type="button"
                                        class="list-group-item list-group-item-action applicant-btn splash-applicant {{ (int) $ete->active_application_id === (int) $applicant->id ? 'active is-live' : '' }}"
                                        data-ete="{{ $ete->id }}"
                                        data-applicant="{{ $applicant->id }}">
                                    <strong>
                                        {{ $applicant->first_name }}
                                        {{ $applicant->middle_name }}
                                        {{ $applicant->last_name }}
                                    </strong>
                                    <br>
                                    <small>{{ $applicant->app_number }}</small>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- RIGHT SIDE --}}
                <div class="col-md-8">

                    <div id="empty-rating" class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Select / splash an applicant to show live consolidated rating.
                    </div>

                    <div id="selected-consolidated" class="card d-none">
                        <div class="card-header bg-success text-white">
                            <strong id="selected-name">Selected Applicant</strong>
                            <br>
                            <small id="selected-app-number"></small>
                        </div>

                        <div class="card-body">

                            <div class="row text-center mb-3">

                                <div class="col-md-3 mb-2">
                                    <div class="ete-metric">
                                        <small>Education</small>
                                        <strong id="education-avg">0.00</strong>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-2">
                                    <div class="ete-metric">
                                        <small>Training</small>
                                        <strong id="training-avg">0.00</strong>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-2">
                                    <div class="ete-metric">
                                        <small>Experience</small>
                                        <strong id="experience-avg">0.00</strong>
                                    </div>
                                </div>

                                <div class="col-md-3 mb-2">
                                    <div class="ete-metric">
                                        <small>Total Rating</small>
                                        <strong id="total-avg">0.00</strong>
                                    </div>
                                </div>

                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong><i class="fas fa-users"></i> Active Evaluator Buttons</strong>
                                <span class="badge badge-light border" id="rating-progress">0 / 0 completed</span>
                            </div>

                            <div id="active-evaluator-buttons" class="mb-3"></div>

                            <div class="alert alert-light border mb-0">
                                <i class="fas fa-sync-alt"></i>
                                This consolidated rating updates live based on evaluator ratings.
                            </div>

                        </div>
                    </div>

                </div>

            </div>
        </div>

    </div>

</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function () {

    let eteId = "{{ $ete->id }}";
    let selectedApplicantId = @json($ete->active_application_id);

    $('.splash-applicant').on('click', function () {
        let btn = $(this);
        let applicantId = btn.data('applicant');

        selectedApplicantId = applicantId;

        $.ajax({
            url: "{{ route('eteSplashApplicant') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                ete_id: eteId,
                application_id: applicantId
            },
            success: function () {
                $('.applicant-btn').removeClass('active');
                btn.addClass('active');

                $('#empty-rating').addClass('d-none');
                $('#selected-consolidated').removeClass('d-none');

                loadSelectedConsolidated();
            }
        });
    });

    function markActiveApplicant(applicantId) {
        $('.applicant-btn').removeClass('active is-live');

        if (applicantId) {
            $('.applicant-btn[data-applicant="' + applicantId + '"]').addClass('active is-live');
        }
    }

    function renderEvaluatorButtons(evaluators) {
        let html = '';

        if (!evaluators || evaluators.length === 0) {
            $('#active-evaluator-buttons').html('<div class="text-muted">No evaluators assigned.</div>');
            return;
        }

        $.each(evaluators, function (_, evaluator) {
            let statusClass = evaluator.completed ? 'badge-success' : 'badge-secondary';
            let statusText = evaluator.completed ? 'Rated' : 'Waiting';

            html += `
                <div class="ete-evaluator-link">
                    <div>
                        <strong>${evaluator.name || 'Evaluator'}</strong>
                        <div class="text-muted small">Total: ${evaluator.total_score}</div>
                    </div>
                    <div class="text-right">
                        <span class="badge ${statusClass} mr-1">${statusText}</span>
                        <a href="${evaluator.url}" target="_blank" class="btn btn-sm btn-success">
                            <i class="fas fa-star"></i> Evaluate
                        </a>
                    </div>
                </div>
            `;
        });

        $('#active-evaluator-buttons').html(html);
    }

    function loadSelectedConsolidated() {
        $.ajax({
            url: "{{ route('eteSelectedApplicantConsolidated', $ete->id) }}",
            type: "GET",
            success: function (response) {
                if (!response.success) {
                    $('#empty-rating').removeClass('d-none');
                    $('#selected-consolidated').addClass('d-none');
                    return;
                }

                selectedApplicantId = response.application_id;
                markActiveApplicant(response.application_id);

                $('#selected-name').text(response.name);
                $('#selected-app-number').text(response.app_number);

                $('#education-avg').text(response.education_avg);
                $('#training-avg').text(response.training_avg);
                $('#experience-avg').text(response.experience_avg);
                $('#total-avg').text(response.total_avg);
                $('#rating-progress').text(response.completed_count + ' / ' + response.evaluator_count + ' completed');
                renderEvaluatorButtons(response.evaluators);

                $('#empty-rating').addClass('d-none');
                $('#selected-consolidated').removeClass('d-none');
            }
        });
    }

    setInterval(function () {
        loadSelectedConsolidated();
    }, 1500);

    loadSelectedConsolidated();

});
</script>
@endsection
