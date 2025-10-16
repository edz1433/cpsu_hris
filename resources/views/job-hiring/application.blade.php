@extends('layouts.master')

@section('body')
@php
    $current_route = request()->route()->getName();
@endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12 mb-2">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-briefcase"></i> Application List
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example1" class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>App No.</th>
                                    <th>Applicant Name</th>
                                    <th>Position</th>
                                    <th>Sex</th>
                                    <th>Mobile</th>
                                    <th>Email</th>
                                    <th>Files</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1; @endphp
                                @foreach($applications as $app)
                                <tr id="tr-{{ $app->id }}">
                                    <td class="align-middle">{{ $no++ }}</td>
                                    <td class="align-middle">{{ $app->app_number }}</td>
                                    <td class="align-middle">{{ $app->first_name }} {{ $app->middle_name }} {{ $app->last_name }}</td>
                                    <td class="align-middle">{{ $app->position }}</td>
                                    <td class="align-middle">{{ ucfirst($app->sex) }}</td>
                                    <td class="align-middle">{{ $app->mobile }}</td>
                                    <td class="align-middle">{{ $app->email }}</td>
                                    <td class="align-middle">
                                        @if($app->status != 0)
                                            <a href="{{ asset('storage/' . $app->pds) }}" target="_blank">PDS</a> |
                                            <a href="{{ asset('storage/' . $app->wes) }}" target="_blank">WES</a> |
                                            <a href="{{ asset('storage/' . $app->intent) }}" target="_blank">Intent</a> |
                                            <a href="{{ asset('storage/' . $app->resume) }}" target="_blank">Resume</a> |
                                            <a href="{{ asset('storage/' . $app->tor) }}" target="_blank">TOR</a>
                                        @else
                                            Waiting for Records
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        @php
                                            $status_labels = ['Pending', 'Reviewed', 'Qualified', 'Disqualified'];
                                            $badge_colors = ['secondary', 'info', 'success', 'danger'];
                                        @endphp
                                        <span class="badge badge-{{ $badge_colors[$app->status] ?? 'secondary' }}">
                                            {{ $status_labels[$app->status] ?? 'Unknown' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button value="{{ $app->id }}" class="btn btn-danger btn-sm app-delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" role="dialog" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="reviewForm" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reviewModalLabel">Review Application</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="application_no">Routing #</label>
                        <input type="text" name="application_no" id="application_no" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
