@extends('layouts.master')

@section('body')
<style>
    .ete-rate-shell .card,
    .ete-rate-shell .alert {
        border-radius: 16px;
    }

    .ete-rate-shell > .card {
        border: 0;
        box-shadow: 0 8px 28px rgba(15, 23, 42, .07);
    }

    .ete-rate-header {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: space-between;
        padding: 18px 20px;
    }

    .ete-active-banner {
        background: #f8fafc;
        border: 1px solid #dfe4ea;
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 15px;
    }

    .ete-active-banner strong {
        display: block;
        font-size: 1rem;
    }

    .ete-rate-shell .form-control {
        border-radius: 9px;
        min-height: 42px;
    }

    @media (max-width: 767.98px) {
        .ete-rate-shell {
            padding-left: 8px;
            padding-right: 8px;
        }
        .ete-rate-header {
            align-items: flex-start;
            flex-direction: column;
        }
        .ete-active-banner .badge {
            float: none !important;
            margin-top: 10px;
        }
        .ete-rate-shell .table-responsive {
            border: 0;
        }
        .ete-rate-shell .experience-table thead {
            display: none;
        }
        .ete-rate-shell .experience-table,
        .ete-rate-shell .experience-table tbody,
        .ete-rate-shell .experience-table tr,
        .ete-rate-shell .experience-table td {
            display: block;
            width: 100%;
        }
        .ete-rate-shell .experience-table tr {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 10px;
            padding: 10px;
        }
        .ete-rate-shell .experience-table td {
            border: 0;
            padding: 5px 0;
        }
        .ete-rate-shell .experience-table td::before {
            color: #6c757d;
            content: attr(data-label);
            display: block;
            font-size: .75rem;
            font-weight: 700;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .ete-rate-shell .rating-form button[type="submit"] {
            width: 100%;
        }
        .ete-rate-shell .save-status {
            display: block;
            margin: 10px 0 0 !important;
            text-align: center;
        }
    }
</style>

<div class="container-fluid ete-rate-shell">

    <div class="card card-info card-outline">

        <div class="ete-rate-header">
            <h3 class="card-title">
                <i class="fas fa-star"></i>
                Evaluator Rating - {{ $ete->job->title ?? '' }}
            </h3>

            <span class="badge badge-success p-2">
                {{ $evaluator->lname ?? '' }},
                {{ $evaluator->fname ?? '' }}
            </span>
        </div>

        <div class="card-body">

            <div id="active-banner" class="ete-active-banner d-none">
                <small class="text-muted">Currently cast applicant</small>
                <strong id="active-applicant-name"></strong>
                <span id="active-applicant-number" class="badge badge-light border"></span>
                <span class="badge badge-success float-right"><i class="fas fa-bolt"></i> Live autosave</span>
            </div>

            <div id="waiting-box" class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Waiting for HR to cast an applicant...
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
                                            <label>Evaluation Date</label>
                                            <input type="date"
                                                   name="evaluation_date"
                                                   class="form-control"
                                                   value="{{ optional($rating->evaluation_date ?? $ete->evaluation_date)->format('Y-m-d') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Present Position</label>
                                            <input type="text"
                                                   name="present_position"
                                                   class="form-control"
                                                   value="{{ $rating->present_position }}"
                                                   placeholder="Applicant's present position">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>College / Campus / Department</label>
                                            <input type="text"
                                                   name="college_department"
                                                   class="form-control"
                                                   value="{{ $rating->college_department }}"
                                                   placeholder="Office or department">
                                        </div>
                                    </div>
                                </div>

                                <div class="card border mb-3">
                                    <div class="card-header bg-light">
                                        <strong>Minimum Requirements (70 points)</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach([
                                                'education_met' => 'Education',
                                                'experience_met' => 'Experience',
                                                'eligibility_met' => 'Eligibility',
                                                'training_met' => 'Training',
                                            ] as $field => $label)
                                                <div class="col-sm-6 col-lg-3 mb-2">
                                                    <div class="border rounded p-2 h-100">
                                                        <strong class="d-block mb-2">{{ $label }}</strong>
                                                        <label class="mr-3 mb-0">
                                                            <input type="radio" name="{{ $field }}" value="1"
                                                                {{ $rating->{$field} === true ? 'checked' : '' }}> Met
                                                        </label>
                                                        <label class="mb-0">
                                                            <input type="radio" name="{{ $field }}" value="0"
                                                                {{ $rating->{$field} === false ? 'checked' : '' }}> Not met
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="row">

                                    <div class="col-6 col-lg-3">
                                        <div class="form-group">
                                            <label>Minimum</label>
                                            <input type="number"
                                                   class="form-control minimum-score font-weight-bold"
                                                   value="{{ $rating->minimum_requirement_score }}"
                                                   readonly>
                                            <small class="text-muted">Maximum: 70</small>
                                        </div>
                                    </div>

                                    <div class="col-6 col-lg-3">
                                        <div class="form-group">
                                            <label>Education Score</label>
                                            <input type="number"
                                                   name="education_score"
                                                   class="form-control education-score font-weight-bold"
                                                   value="{{ $rating->education_score }}"
                                                   readonly>
                                            <small class="text-muted">Maximum: 10</small>
                                        </div>
                                    </div>

                                    <div class="col-6 col-lg-3">
                                        <div class="form-group">
                                            <label>Training Score</label>
                                            <input type="number"
                                                   name="training_score"
                                                   class="form-control training-score font-weight-bold"
                                                   value="{{ $rating->training_score }}"
                                                   readonly>
                                            <small class="text-muted">Maximum: 5</small>
                                        </div>
                                    </div>

                                    <div class="col-6 col-lg-3">
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

                                @php
                                    $savedEducation = $rating->education_ratings ?? [];
                                    $savedTraining = $rating->training_ratings ?? [];
                                    $educationItems = [
                                        'additional_four_year_course' => ['Additional 4-year course completed', 2],
                                        'masteral_1_18' => ['1-18 masteral units', 1],
                                        'masteral_19_30' => ['19-30 masteral units', 2],
                                        'masters_degree' => ["Master's degree completed", 4],
                                        'doctoral_1_18' => ['1-18 doctoral units', 5],
                                        'doctoral_19_36' => ['19-36 doctoral units', 6],
                                        'doctoral_degree' => ['Doctoral degree completed', 10],
                                    ];
                                @endphp

                                <div class="row">
                                    <div class="col-lg-6">
                                        <h6 class="font-weight-bold"><i class="fas fa-graduation-cap"></i> Education Credits</h6>
                                        <div class="list-group mb-3">
                                            @foreach($educationItems as $key => [$label, $credit])
                                                <label class="list-group-item mb-0">
                                                    <input type="hidden" name="education_ratings[{{ $key }}]" value="0">
                                                    <input type="checkbox"
                                                           class="education-credit-item mr-2"
                                                           name="education_ratings[{{ $key }}]"
                                                           value="1"
                                                           data-credit="{{ $credit }}"
                                                           {{ !empty($savedEducation[$key]) ? 'checked' : '' }}>
                                                    {{ $label }}
                                                    <span class="badge badge-success float-right">{{ $credit }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <h6 class="font-weight-bold"><i class="fas fa-chalkboard-teacher"></i> Training Credits</h6>
                                        <div class="list-group mb-3">
                                            <label class="list-group-item mb-0">
                                                <input type="hidden" name="training_ratings[scholarship_grant]" value="0">
                                                <input type="checkbox"
                                                       class="training-credit-item mr-2"
                                                       name="training_ratings[scholarship_grant]"
                                                       value="1"
                                                       data-credit="3"
                                                       {{ !empty($savedTraining['scholarship_grant']) ? 'checked' : '' }}>
                                                Relevant study or scholarship grant
                                                <span class="badge badge-success float-right">3</span>
                                            </label>
                                            <label class="list-group-item mb-0">
                                                <input type="hidden" name="training_ratings[leadership_seminar]" value="0">
                                                <input type="checkbox"
                                                       class="training-credit-item mr-2"
                                                       name="training_ratings[leadership_seminar]"
                                                       value="1"
                                                       data-credit="2"
                                                       {{ !empty($savedTraining['leadership_seminar']) ? 'checked' : '' }}>
                                                Comparable leadership seminar
                                                <span class="badge badge-success float-right">2</span>
                                            </label>
                                            <div class="list-group-item">
                                                <label>Relevant in-service training hours</label>
                                                <input type="number"
                                                       class="form-control training-hours"
                                                       name="training_ratings[relevant_hours]"
                                                       min="0"
                                                       step="1"
                                                       value="{{ $savedTraining['relevant_hours'] ?? 0 }}">
                                                <small class="text-muted">1 point for every completed 50 hours.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="font-weight-bold">
                                    <i class="fas fa-briefcase"></i>
                                    Experience Rating
                                </h6>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm bg-white experience-table">
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
                                                    <td data-label="Year" class="align-middle font-weight-bold">
                                                        {{ $year }}
                                                    </td>

                                                    <td data-label="Length of Experience">
                                                        <input type="text"
                                                               name="experience_years[{{ $year }}][length]"
                                                               class="form-control form-control-sm"
                                                               value="{{ $savedExperience[$year]['length'] ?? '' }}"
                                                               placeholder="Example: /12">
                                                    </td>

                                                    <td data-label="Level of Experience">
                                                        <input type="number"
                                                               name="experience_years[{{ $year }}][level]"
                                                               class="form-control form-control-sm"
                                                               min="0"
                                                               step="0.01"
                                                               value="{{ $savedExperience[$year]['level'] ?? 0 }}">
                                                    </td>

                                                    <td data-label="Credit">
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
                                            <label>Total Rating (out of 100)</label>
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
            activeApplicant = null;
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
            window.location.reload();
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
        let minimum = 70;
        let allMinimumSelected = true;
        let allMinimumMet = true;

        $.each(['education_met', 'experience_met', 'eligibility_met', 'training_met'], function (_, field) {
            let selected = form.find('input[name="' + field + '"]:checked');
            if (!selected.length) {
                allMinimumSelected = false;
                allMinimumMet = false;
            } else if (selected.val() !== '1') {
                allMinimumMet = false;
            }
        });

        if (!allMinimumSelected || !allMinimumMet) minimum = 0;

        let education = 0;
        form.find('.education-credit-item:checked').each(function () {
            education += parseFloat($(this).data('credit')) || 0;
        });
        education = Math.min(10, education);

        let training = 0;
        form.find('.training-credit-item:checked').each(function () {
            training += parseFloat($(this).data('credit')) || 0;
        });
        training += Math.floor((parseFloat(form.find('.training-hours').val()) || 0) / 50);
        training = Math.min(5, training);

        let experience = 0;

        form.find('.experience-credit').each(function () {
            experience += parseFloat($(this).val()) || 0;
        });

        if (experience > 15) {
            experience = 15;
        }

        let total = minimum + education + training + experience;

        form.find('.minimum-score').val(minimum.toFixed(2));
        form.find('.education-score').val(education.toFixed(2));
        form.find('.training-score').val(training.toFixed(2));
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
                form.find('.minimum-score').val(response.minimum_requirement_score);
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

    $(document).on('input change', '.education-credit-item, .training-credit-item, .training-hours, .experience-credit, .rating-form input[type="radio"]', function () {
        let form = $(this).closest('form');
        computeFormTotal(form);
        scheduleAutosave(form);
    });

    $(document).on('change input', '.rating-form input[type="text"], .rating-form input[type="date"], .rating-form textarea, .rating-form input[name$="[level]"]', function () {
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
