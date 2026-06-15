@extends('layouts.master')

@section('body')
<style>
    .ete-rate-shell .card,
    .ete-rate-shell .alert {
        border-radius: 8px;
    }

    .ete-active-banner {
        background: #f8fafc;
        border: 1px solid #dfe4ea;
        border-radius: 8px;
        padding: 12px 14px;
        margin-bottom: 15px;
    }

    .ete-active-banner strong {
        display: block;
        font-size: 1rem;
    }
</style>

<div class="container-fluid ete-rate-shell">

    <div class="card card-info card-outline">

        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-star"></i>
                Evaluator Rating - {{ $ete->job->title ?? '' }}
            </h3>

            <span class="badge badge-success float-right">
                {{ $evaluator->lname ?? '' }},
                {{ $evaluator->fname ?? '' }}
            </span>
        </div>

        <div class="card-body">

            <div id="active-banner" class="ete-active-banner d-none">
                <small class="text-muted">Current splash applicant</small>
                <strong id="active-applicant-name"></strong>
                <span id="active-applicant-number" class="badge badge-light border"></span>
                <span class="badge badge-success float-right"><i class="fas fa-bolt"></i> Live autosave</span>
            </div>

            <div id="waiting-box" class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Waiting for HR to select/splash an applicant...
            </div>

            @foreach($ratings as $rating)
                @php
                    $app = $rating->application;

                    $savedExperience = [];

                    if (!empty($rating->experience_year_ratings)) {
                        $savedExperience = is_array($rating->experience_year_ratings)
                            ? $rating->experience_year_ratings
                            : json_decode($rating->experience_year_ratings, true);
                    }
                @endphp

                <div class="rating-panel d-none"
                     id="rating-panel-{{ $app->id }}">

                    <div class="card">

                        <div class="card-header bg-success text-white">
                            <strong>
                                Applicant:
                                {{ $app->first_name }}
                                {{ $app->middle_name }}
                                {{ $app->last_name }}
                            </strong>
                            <br>
                            <small>{{ $app->app_number }}</small>
                        </div>

                        <div class="card-body">

                            <form class="rating-form" data-id="{{ $rating->id }}">
                                @csrf

                                <input type="hidden" name="evaluate_id" value="{{ $rating->id }}">

                                <div class="row">

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Education Score</label>
                                            <input type="number"
                                                   name="education_score"
                                                   class="form-control score-input"
                                                   min="0"
                                                   max="10"
                                                   step="0.01"
                                                   value="{{ $rating->education_score }}">
                                            <small class="text-muted">Max: 10</small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Training Score</label>
                                            <input type="number"
                                                   name="training_score"
                                                   class="form-control score-input"
                                                   min="0"
                                                   max="5"
                                                   step="0.01"
                                                   value="{{ $rating->training_score }}">
                                            <small class="text-muted">Max: 5</small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Experience Score</label>
                                            <input type="number"
                                                   name="experience_score_display"
                                                   class="form-control experience-score font-weight-bold"
                                                   value="{{ $rating->experience_score }}"
                                                   readonly>
                                            <small class="text-muted">Auto-computed, Max: 15</small>
                                        </div>
                                    </div>

                                </div>

                                <hr>

                                <h6 class="font-weight-bold">
                                    <i class="fas fa-briefcase"></i>
                                    Experience Rating
                                </h6>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm bg-white">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width: 15%;">Year</th>
                                                <th style="width: 30%;">Length of Experience</th>
                                                <th style="width: 25%;">Level of Experience</th>
                                                <th style="width: 30%;">Credit</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach($years as $year)
                                                <tr>
                                                    <td class="align-middle font-weight-bold">
                                                        {{ $year }}
                                                    </td>

                                                    <td>
                                                        <input type="text"
                                                               name="experience_years[{{ $year }}][length]"
                                                               class="form-control form-control-sm"
                                                               value="{{ $savedExperience[$year]['length'] ?? '' }}"
                                                               placeholder="Example: /12">
                                                    </td>

                                                    <td>
                                                        <input type="number"
                                                               name="experience_years[{{ $year }}][level]"
                                                               class="form-control form-control-sm"
                                                               min="0"
                                                               step="0.01"
                                                               value="{{ $savedExperience[$year]['level'] ?? 0 }}">
                                                    </td>

                                                    <td>
                                                        <input type="number"
                                                               name="experience_years[{{ $year }}][credit]"
                                                               class="form-control form-control-sm experience-credit"
                                                               min="0"
                                                               step="0.01"
                                                               value="{{ $savedExperience[$year]['credit'] ?? 0 }}">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row">

                                    <div class="col-md-4 offset-md-8">
                                        <div class="form-group">
                                            <label>Total Rating</label>
                                            <input type="number"
                                                   class="form-control total-score font-weight-bold"
                                                   value="{{ $rating->total_score }}"
                                                   readonly>
                                        </div>
                                    </div>

                                </div>

                                <div class="form-group">
                                    <label>Remarks</label>
                                    <textarea name="remarks"
                                              class="form-control"
                                              rows="3"
                                              placeholder="Remarks...">{{ $rating->remarks }}</textarea>
                                </div>

                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Save Rating
                                </button>

                                <span class="save-status text-success ml-2">
                                    <i class="fas fa-check-circle"></i> Ready
                                </span>

                            </form>

                        </div>

                    </div>

                </div>
            @endforeach

        </div>

    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function () {

    let eteId = "{{ $ete->id }}";
    let activeApplicant = null;

    function showApplicant(applicantId) {
        if (!applicantId) {
            $('#waiting-box').removeClass('d-none');
            $('.rating-panel').addClass('d-none');
            $('#active-banner').addClass('d-none');
            return;
        }

        if (activeApplicant == applicantId) {
            return;
        }

        activeApplicant = applicantId;

        $('#waiting-box').addClass('d-none');
        $('.rating-panel').addClass('d-none');

        let panel = $('#rating-panel-' + applicantId);

        if (panel.length) {
            panel.removeClass('d-none');
            computeFormTotal(panel.find('.rating-form'));
            $('#active-applicant-name').text(panel.find('.card-header strong').text().replace('Applicant:', '').trim());
            $('#active-applicant-number').text(panel.find('.card-header small').text().trim());
            $('#active-banner').removeClass('d-none');
        } else {
            $('#waiting-box')
                .removeClass('d-none')
                .html('<i class="fas fa-exclamation-circle"></i> This applicant is not assigned to you.');
        }
    }

    function checkActiveApplicant() {
        $.ajax({
            url: "{{ route('eteActiveApplicant', $ete->id) }}",
            type: "GET",
            success: function (response) {
                showApplicant(response.active_application_id);
            }
        });
    }

    function computeFormTotal(form) {
        let education = parseFloat(form.find('input[name="education_score"]').val()) || 0;
        let training = parseFloat(form.find('input[name="training_score"]').val()) || 0;

        let experience = 0;

        form.find('.experience-credit').each(function () {
            experience += parseFloat($(this).val()) || 0;
        });

        if (experience > 15) {
            experience = 15;
        }

        let total = education + training + experience;

        form.find('.experience-score').val(experience.toFixed(2));
        form.find('.total-score').val(total.toFixed(2));
    }

    function setStatus(form, icon, text, className) {
        form.find('.save-status')
            .removeClass('text-success text-muted text-danger')
            .addClass(className)
            .html('<i class="fas ' + icon + '"></i> ' + text);
    }

    function saveForm(form, silent) {
        let btn = form.find('button[type="submit"]');

        computeFormTotal(form);
        setStatus(form, 'fa-spinner fa-spin', silent ? 'Autosaving...' : 'Saving...', 'text-muted');

        if (!silent) {
            btn.prop('disabled', true)
               .html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        }

        $.ajax({
            url: "{{ route('eteRatingUpdateAjax') }}",
            type: "POST",
            data: form.serialize(),
            success: function (response) {
                form.find('input[name="education_score"]').val(response.education_score);
                form.find('input[name="training_score"]').val(response.training_score);
                form.find('.experience-score').val(response.experience_score);
                form.find('.total-score').val(response.total_score);
                setStatus(form, 'fa-check-circle', 'Saved live', 'text-success');
            },
            error: function () {
                setStatus(form, 'fa-exclamation-circle', 'Unable to save', 'text-danger');

                if (!silent) {
                    alert('Unable to save rating. Please check the form.');
                }
            },
            complete: function () {
                if (!silent) {
                    btn.prop('disabled', false)
                       .html('<i class="fas fa-save"></i> Save Rating');
                }
            }
        });
    }

    function scheduleAutosave(form) {
        clearTimeout(form.data('autosaveTimer'));
        setStatus(form, 'fa-clock', 'Pending autosave...', 'text-muted');

        form.data('autosaveTimer', setTimeout(function () {
            saveForm(form, true);
        }, 800));
    }

    $(document).on('input', '.score-input, .experience-credit', function () {
        let form = $(this).closest('form');
        computeFormTotal(form);
        scheduleAutosave(form);
    });

    $(document).on('change input', '.rating-form input[type="text"], .rating-form textarea, .rating-form input[name$="[level]"]', function () {
        scheduleAutosave($(this).closest('form'));
    });

    $(document).on('submit', '.rating-form', function (e) {
        e.preventDefault();

        let form = $(this);
        clearTimeout(form.data('autosaveTimer'));
        saveForm(form, false);
    });

    setInterval(checkActiveApplicant, 1500);
    checkActiveApplicant();

});
</script>
@endsection
