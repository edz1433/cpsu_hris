@extends('layouts.master')

@section('body')
<style>
    .ete-list-page .card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 8px 28px rgba(15, 23, 42, .07);
    }
    .ete-list-head {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        justify-content: space-between;
        padding: 18px 20px;
    }
    .ete-list-title {
        font-size: 1.15rem;
        font-weight: 700;
        margin: 0;
    }
    .ete-list-page .table td,
    .ete-list-page .table th {
        vertical-align: middle;
    }
    .ete-list-page .badge {
        white-space: normal;
    }
    .ete-list-page .modal-content {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
    }
    @media (max-width: 767.98px) {
        .ete-list-page {
            padding-left: 8px;
            padding-right: 8px;
        }
        .ete-list-head .btn {
            width: 100%;
        }
        .ete-responsive-table thead {
            display: none;
        }
        .ete-responsive-table,
        .ete-responsive-table tbody,
        .ete-responsive-table tr,
        .ete-responsive-table td {
            display: block;
            width: 100%;
        }
        .ete-responsive-table tr {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            margin-bottom: 12px;
            overflow: hidden;
            padding: 8px 12px;
        }
        .ete-responsive-table td {
            border: 0 !important;
            min-height: 38px;
            padding: 8px 0;
            text-align: right !important;
        }
        .ete-responsive-table td::before {
            color: #6c757d;
            content: attr(data-label);
            float: left;
            font-weight: 700;
            margin-right: 15px;
            text-align: left;
        }
        .ete-responsive-table td:last-child {
            border-top: 1px solid #edf0f2 !important;
            margin-top: 4px;
            padding-top: 12px;
        }
        .ete-responsive-table td:last-child .btn {
            min-width: 44px;
        }
        .ete-list-page .modal-dialog {
            margin: 8px;
        }
        .ete-list-page .modal-footer {
            align-items: stretch;
            flex-direction: column-reverse;
        }
        .ete-list-page .modal-footer .btn {
            margin: 4px 0;
            width: 100%;
        }
    }
</style>
<div class="container-fluid ete-list-page">
    <div class="row">
        <div class="col-lg-12 mb-2">
            <div class="card card-info card-outline">

                <div class="ete-list-head">
                    <h1 class="ete-list-title">
                        <i class="fas fa-clipboard-check"></i> ETE Evaluation List
                    </h1>

                    <button class="btn btn-success"
                            data-toggle="modal"
                            data-target="#add-ete-evaluation">
                        <i class="fas fa-plus"></i> ADD ETE EVALUATION
                    </button>
                </div>

                <div class="card-body">

                    <div class="table-responsive">
                        <table id="example1" class="table table-hover ete-responsive-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>Position</th>
                                    <th>Evaluation Date</th>
                                    <th>Experience Years</th>
                                    <th>Total Applicants</th>
                                    <th>Total Evaluators</th>
                                    <th>Panel Evaluators</th>
                                    <th>Date Created</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($eteEvaluations as $ete)
                                    <tr>
                                        <td data-label="No.">{{ $loop->iteration }}</td>

                                        <td data-label="Position"><strong>{{ $ete->job->title ?? 'N/A' }}</strong></td>

                                        <td data-label="Evaluation Date">
                                            {{ $ete->evaluation_date
                                                ? $ete->evaluation_date->format('M. d, Y h:i A')
                                                : '' }}
                                        </td>

                                        <td data-label="Experience Years">
                                            <span class="badge badge-secondary">
                                                {{ $ete->experience_years ?? 'N/A' }}
                                            </span>
                                        </td>

                                        <td data-label="Applicants" class="text-center">
                                            {{ $ete->employeeEvaluates->groupBy('application_id')->count() }}
                                        </td>

                                        <td data-label="Evaluators" class="text-center">
                                            {{ $ete->evaluators->count() }}
                                        </td>

                                        <td data-label="Panel">
                                            @forelse($ete->evaluators as $panel)
                                                <span class="badge badge-info mb-1">
                                                    {{ $panel->employee->lname ?? '' }},
                                                    {{ $panel->employee->fname ?? '' }}
                                                </span>
                                            @empty
                                                <span class="text-muted">No evaluator</span>
                                            @endforelse
                                        </td>

                                        <td data-label="Created">
                                            {{ $ete->created_at
                                                ? $ete->created_at->format('M. d, Y h:i A')
                                                : '' }}
                                        </td>

                                        <td data-label="Actions" class="text-center">
                                            <a href="{{ route('eteEvaluationShow', $ete->id) }}"
                                               class="btn btn-sm btn-primary"
                                               title="Manage ETE">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <a href="{{ route('eteConsolidatedScreen', $ete->id) }}"
                                               target="_blank"
                                               class="btn btn-sm btn-warning"
                                               title="Consolidated Screen">
                                                <i class="fas fa-tv"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>


<!-- Add ETE Evaluation Modal -->
<div class="modal fade" id="add-ete-evaluation" tabindex="-1" role="dialog" aria-labelledby="addEteEvaluationLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">

        <form action="{{ route('eteEvaluationStore') }}" method="POST">
            @csrf

            <div class="modal-content">

                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white" id="addEteEvaluationLabel">
                        <i class="fas fa-clipboard-check"></i> Create ETE Evaluation
                    </h5>

                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body" style="background-color: #e9ecef;">

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Position</label>
                                <select name="jid" class="form-control select2" required>
                                    <option value="">Select Position</option>
                                    @foreach($jobs as $job)
                                        <option value="{{ $job->id }}">
                                            {{ $job->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Evaluation Date</label>
                                <input type="datetime-local"
                                       name="evaluation_date"
                                       class="form-control"
                                       value="{{ now()->format('Y-m-d\TH:i') }}"
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Experience Years</label>
                        <input type="text"
                               name="experience_years"
                               class="form-control"
                               placeholder="Example: 2021-2025"
                               required>

                        <small class="text-muted">
                            Use range format like <strong>2021-2025</strong>. These years will appear in the evaluator rating form.
                        </small>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label>Panel Evaluators</label>

                        <select name="evaluators[]" class="form-control select2" multiple required>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">
                                    {{ $employee->lname }},
                                    {{ $employee->fname }}
                                    {{ $employee->mname }}
                                </option>
                            @endforeach
                        </select>

                        <small class="text-muted">
                            Select one or more evaluators. These are employees who will evaluate applicants.
                        </small>
                    </div>

                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i>
                        When saved, all applicants with status <strong>Reviewing</strong> under the selected position will automatically be added for ETE evaluation.
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Create ETE
                    </button>
                </div>

            </div>

        </form>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function () {
    $('#add-ete-evaluation').on('shown.bs.modal', function () {
        $('.select2').select2({
            dropdownParent: $('#add-ete-evaluation'),
            width: '100%',
            placeholder: 'Search...'
        });
    });
});
</script>
@endsection
