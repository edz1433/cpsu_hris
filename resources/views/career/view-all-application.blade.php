@extends('layouts.master')

@section('body')
@php
    $has_filter = request()->hasAny(['position_id', 'checked', 'search', 'date_from', 'date_to']);
@endphp
<style>
    .application-filter .select2-container--default .select2-selection--single {
        height: calc(1.8125rem + 2px);
        border: 1px solid #ced4da;
    }

    .application-filter .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #495057;
        font-size: .875rem;
        font-weight: 400;
        line-height: calc(1.8125rem + 2px);
        padding-left: .5rem;
        padding-right: 1.75rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .application-filter .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: calc(1.8125rem + 2px);
    }
</style>

<div class="container-fluid">
    <div class="row">

        {{-- Job Applications List - All Applications --}}
        <div class="col-lg-12">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users"></i> Job Applications ({{ number_format($jobapplication->total()) }} {{ $has_filter ? 'matched' : 'total' }})
                    </h3>
                    <div class="card-tools">
                        <span class="badge bg-info">
                            Showing {{ number_format($jobapplication->firstItem() ?? 0) }}&ndash;{{ number_format($jobapplication->lastItem() ?? 0) }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('viewAllApplication') }}" class="application-filter border rounded bg-light p-3 mb-3">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <div class="form-group mb-md-0">
                                    <label for="filter_position_id">Position</label>
                                    <select name="position_id" id="filter_position_id" class="form-control form-control-sm select2">
                                        <option value="">All Positions</option>
                                        @foreach($jobs as $job)
                                            <option value="{{ $job->id }}" {{ (string) request('position_id') === (string) $job->id ? 'selected' : '' }}>
                                                {{ $job->title }}{{ !empty($job->plantilla_item_no) ? ' - Plantilla No. '.$job->plantilla_item_no : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-md-0">
                                    <label for="filter_checked">Status</label>
                                    <select name="checked" id="filter_checked" class="form-control form-control-sm">
                                        <option value="">All Statuses</option>
                                        <option value="0" {{ request('checked') === '0' ? 'selected' : '' }}>Pending Review</option>
                                        <option value="1" {{ request('checked') === '1' ? 'selected' : '' }}>Forwarded to President Office</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-md-0">
                                    <label for="filter_search">Applicant / Email</label>
                                    <input type="text" name="search" id="filter_search" class="form-control form-control-sm"
                                           value="{{ request('search') }}" placeholder="Last name, first name or email">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-md-0">
                                    <label for="filter_date_from">Applied From</label>
                                    <input type="date" name="date_from" id="filter_date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-md-0">
                                    <label for="filter_date_to">Applied To</label>
                                    <input type="date" name="date_to" id="filter_date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12 text-right">
                                <a href="{{ route('viewAllApplication') }}" class="btn btn-default btn-sm">
                                    <i class="fas fa-undo"></i> Reset
                                </a>
                                <button type="submit" class="btn btn-info btn-sm">
                                    <i class="fas fa-filter"></i> Apply Filter
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Applicant Name</th>
                                    <th>Position Applied</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Applied Date</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = $jobapplication->firstItem() ?? 1; @endphp
                                @forelse($jobapplication as $application)
                                <tr>
                                    <td class="align-middle">{{ $no++ }}</td>
                                    <td class="align-middle">
                                        <strong class="text-uppercase">
                                            {{ $application->last_name }},
                                            {{ $application->first_name }}
                                            {{ !empty($application->middle_name) ? strtoupper(substr($application->middle_name, 0, 1)).'.' : '' }}
                                        </strong>
                                    </td>
                                    <td class="align-middle">
                                        {{ $application->title }}
                                        @if(!empty($application->plantilla_item_no))
                                            <br>
                                            <small class="text-muted">Plantilla No. {{ $application->plantilla_item_no }}</small>
                                        @endif
                                        <br>
                                        @if($application->job_type == 1)
                                            <span class="badge bg-success">Non-Teaching</span>
                                        @else
                                            <span class="badge bg-primary">Teaching</span>
                                        @endif
                                    </td>
                                    <td class="align-middle">{{ $application->email }}</td>
                                    <td class="align-middle">{{ $application->mobile }}</td>
                                    <td class="align-middle">
                                        {{ \Carbon\Carbon::parse($application->created_at)->format('M d, Y') }}
                                        <br>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($application->created_at)->diffForHumans() }}</small>
                                    </td>
                                    <td class="align-middle text-center">
                                        @if($application->checked == 1)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle"></i> Forwarded to President Office
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-clock"></i> Pending Review
                                            </span>
                                        @endif
                                    </td>
                                    <td class="align-middle text-center">
                                        <a href="{{ route('viewApplication', $application->id) }}"
                                           target="_blank"
                                           class="btn btn-info btn-sm"
                                           title="View Application">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        No job applications found{{ $has_filter ? ' for the selected filter.' : '.' }}
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end">
                        {{ $jobapplication->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
