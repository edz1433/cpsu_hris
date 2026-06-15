@extends('layouts.master')

@section('body')
<style>
    .ete-board .card,
    .ete-board .alert {
        border-radius: 8px;
    }

    .ete-board .active-row {
        background: #e9f7ef;
        box-shadow: inset 4px 0 0 #28a745;
    }

    .ete-board-summary {
        align-items: center;
        background: #f8fafc;
        border: 1px solid #dfe4ea;
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
        margin-bottom: 15px;
    }
</style>

<div class="container-fluid ete-board">

    <div class="card card-warning card-outline">

        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-trophy"></i>
                Consolidated ETE Ranking - {{ $ete->job->title ?? '' }}
            </h3>

            <a href="{{ route('eteEvaluationShow', $ete->id) }}"
               class="btn btn-secondary btn-sm float-right">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="card-body">

            <div class="ete-board-summary">
                <div>
                    <small class="text-muted">Current splash applicant</small>
                    <strong id="activeApplicantText" class="d-block">Waiting for active applicant...</strong>
                </div>
                <span class="badge badge-success"><i class="fas fa-sync-alt"></i> Live</span>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="rankingTable">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center">Rank</th>
                            <th>App No.</th>
                            <th>Applicant Name</th>
                            <th class="text-center">Education</th>
                            <th class="text-center">Training</th>
                            <th class="text-center">Experience</th>
                            <th class="text-center">Total Rating</th>
                            <th class="text-center">Progress</th>
                        </tr>
                    </thead>

                    <tbody id="rankingBody">
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                Loading consolidated ratings...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function () {

    function loadConsolidatedRanking() {
        $.ajax({
            url: "{{ route('eteConsolidatedData', $ete->id) }}",
            type: "GET",
            success: function (response) {
                let rows = '';

                if (!response.success || response.data.length === 0) {
                    rows = `
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                No consolidated rating available.
                            </td>
                        </tr>
                    `;
                    $('#activeApplicantText').text('Waiting for active applicant...');
                } else {
                    let activeName = 'Waiting for active applicant...';

                    $.each(response.data, function (index, item) {
                        let rank = index + 1;
                        let rankBadge = '';
                        let activeClass = '';

                        if (rank === 1) {
                            rankBadge = '<span class="badge badge-success">1st</span>';
                        } else if (rank === 2) {
                            rankBadge = '<span class="badge badge-primary">2nd</span>';
                        } else if (rank === 3) {
                            rankBadge = '<span class="badge badge-warning">3rd</span>';
                        } else {
                            rankBadge = '<span class="badge badge-secondary">' + rank + '</span>';
                        }

                        if (parseInt(item.application_id) === parseInt(response.active_application_id)) {
                            activeClass = 'active-row';
                            activeName = item.name + ' (' + item.app_number + ')';
                        }

                        rows += `
                            <tr class="${activeClass}">
                                <td class="text-center font-weight-bold">${rankBadge}</td>
                                <td>${item.app_number}</td>
                                <td>${item.name}</td>
                                <td class="text-center">${item.education_avg}</td>
                                <td class="text-center">${item.training_avg}</td>
                                <td class="text-center">${item.experience_avg}</td>
                                <td class="text-center font-weight-bold">${item.total_avg}</td>
                                <td class="text-center">
                                    <span class="badge badge-light border">${item.completed_count} / ${item.evaluator_count}</span>
                                </td>
                            </tr>
                        `;
                    });

                    $('#activeApplicantText').text(activeName);
                }

                $('#rankingBody').html(rows);
            }
        });
    }

    loadConsolidatedRanking();
    setInterval(loadConsolidatedRanking, 1500);

});
</script>
@endsection
