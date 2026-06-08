@extends('layouts.master')

@section('body')
@php
    $current_route = request()->route()->getName();
    $status_labels = [
        0 => 'Application Submitted',
        1 => 'Reviewing',
        2 => 'Qualified / Ready for Interview',
        3 => 'Disqualified',
        4 => 'Qualified yet not selected',
        5 => 'Top 5 / Psychological or Pre-Employment Test',
        6 => 'Not Hired',
        7 => 'Hired',
    ];
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
                                        <form method="GET" action="{{ route('applicationReport') }}" target="_blank">
                        <div class="row align-items-end">
                            <div class="col-md-5">
                                <div class="form-group mb-md-0">
                                    <label for="position_id">Position</label>
                                    <select name="position_id" id="position_id" class="form-control form-control-sm">
                                        <option value="">All Positions</option>
                                        @foreach($jobs as $job)
                                            <option value="{{ $job->id }}">{{ $job->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group mb-md-0">
                                    <label for="status">Status</label>
                                    <select name="status" id="status" class="form-control form-control-sm">
                                        <option value="">All Statuses</option>
                                        @foreach($status_labels as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-danger btn-sm btn-block">
                                    <i class="fas fa-file-pdf"></i> Generate
                                </button>
                            </div>
                        </div>
                    </form>
                    <div class="table-responsive mt-3">
                        <table id="example1" class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>App No.</th>
                                    <th>Control No.</th>
                                    <th>Applicant Name</th>
                                    <th>Position</th>
                                    <th>Sex</th>
                                    <th>Mobile</th>
                                    <th>Email</th>
                                    <th>Files</th>
                                    <th>Date Applied</th>
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
                                    <td class="align-middle">{{ $app->ctrl_no }}</td>
                                    <td class="align-middle">{{ $app->first_name }} {{ $app->middle_name }} {{ $app->last_name }}</td>
                                    <td class="align-middle">{{ $app->position }}</td>
                                    <td class="align-middle">{{ ucfirst($app->sex) }}</td>
                                    <td class="align-middle">{{ $app->mobile }}</td>
                                    <td class="align-middle">{{ $app->email }}</td>

                                    {{-- 🔹 File Access --}}
                                    <td class="align-middle text-center">
                                        @if (empty($app->ctrl_no))
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-warning set-ctrl"
                                                    value="{{ $app->id }}"
                                                    data-toggle="modal"
                                                    data-target="#setCtrlModal"
                                                    title="Set Control Number to unlock file access">
                                                <i class="fas fa-key"></i> Set Control No.
                                            </button>
                                        @else
                                            <div class="d-flex flex-wrap" style="gap: 4px;">
                                                <a href="{{ asset('storage/' . $app->pds) }}" class="btn btn-sm btn-outline-primary" target="_blank" title="Personal Data Sheet">
                                                    <i class="fas fa-file-alt"></i> PDS
                                                </a>
                                                <a href="{{ asset('storage/' . $app->wes) }}" class="btn btn-sm btn-outline-info" target="_blank" title="Work Experience Sheet">
                                                    <i class="fas fa-briefcase"></i> WES
                                                </a>
                                                <a href="{{ asset('storage/' . $app->intent) }}" class="btn btn-sm btn-outline-secondary" target="_blank" title="Intent Letter">
                                                    <i class="fas fa-envelope-open-text"></i> Intent
                                                </a>
                                                <a href="{{ asset('storage/' . $app->resume) }}" class="btn btn-sm btn-outline-success" target="_blank" title="Resume">
                                                    <i class="fas fa-user"></i> Resume
                                                </a>
                                                <a href="{{ asset('storage/' . $app->tor) }}" class="btn btn-sm btn-outline-danger" target="_blank" title="Transcript of Records">
                                                    <i class="fas fa-graduation-cap"></i> TOR
                                                </a>                                        
                                                @if(!empty($app->cert_training))
                                                    <a href="{{ asset('storage/' . $app->cert_training) }}"
                                                    class="btn btn-sm btn-outline-warning"
                                                    target="_blank"
                                                    title="Certificate of Training">
                                                        <i class="fas fa-certificate"></i> COT
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle bold">{{ strtoupper($app->created_at->format('M. d, Y h:i A')) }}</td>
                                    {{-- 🔹 Status --}}
                                    <td class="text-center align-middle">
                                        @php
                                            $status_labels = [
                                                0 => 'Application Submitted',
                                                1 => 'Reviewing',
                                                2 => 'Qualified / Ready for Interview',
                                                3 => 'Disqualified',
                                                4 => 'Qualified yet not selected',
                                                5 => 'Top 5 / Psychological or Pre-Employment Test',
                                                6 => 'Not Hired',
                                                7 => 'Hired',
                                            ];

                                            $badge_colors = [
                                                0 => 'secondary',
                                                1 => 'info',
                                                2 => 'success',
                                                3 => 'danger',
                                                4 => 'warning',
                                                5 => 'primary',
                                                6 => 'dark',
                                                7 => 'success',
                                            ];
                                        @endphp
                                        <span class="badge badge-{{ $badge_colors[$app->status] ?? 'secondary' }}">
                                            {{ $status_labels[$app->status] ?? 'Unknown' }}
                                        </span>
                                    </td>
                                    {{-- 🔹 Actions --}}
                                    <td class="text-center align-middle">
                                        @if ($app->status == 1)
                                            {{-- Qualified --}}
                                            <button type="button"
                                                    class="btn btn-sm btn-success q-btn"
                                                    data-app-id="{{ $app->id }}"
                                                    data-toggle="modal"
                                                    data-target="#qualifyModal"
                                                    title="Mark as Qualified / Set Interview">
                                                <i class="fas fa-check"></i>
                                            </button>

                                            {{-- Disqualified --}}
                                            <button type="button"
                                                    class="btn btn-sm btn-danger dq-btn"
                                                    data-app-id="{{ $app->id }}"
                                                    data-toggle="modal"
                                                    data-target="#dqModal"
                                                    title="Disqualify Applicant">
                                                <i class="fas fa-times"></i>
                                            </button>

                                        @elseif ($app->status == 2)
                                            {{-- Move to next or skip --}}
                                            <form method="POST" action="{{ route('updateStatus') }}" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $app->id }}">
                                                <input type="hidden" name="status" value="4">
                                                <button type="submit" class="btn btn-sm btn-warning" title="Not selected for next stage">
                                                    <i class="fas fa-user-clock"></i>
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('updateStatus') }}" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $app->id }}">
                                                <input type="hidden" name="status" value="5">
                                                <button type="submit" class="btn btn-sm btn-primary" title="Select for next stage (Top 5)">
                                                    <i class="fas fa-arrow-right"></i>
                                                </button>
                                            </form>

                                        @elseif ($app->status == 5)
                                            {{-- Not Hired --}}
                                            <form method="POST" action="{{ route('updateStatus') }}" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $app->id }}">
                                                <input type="hidden" name="status" value="6">
                                                <button type="submit" class="btn btn-sm btn-dark" title="Mark as Not Hired">
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                            </form>

                                            {{-- Hired --}}
                                            <form method="POST" action="{{ route('updateStatus') }}" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $app->id }}">
                                                <input type="hidden" name="status" value="7">
                                                <button type="submit" class="btn btn-sm btn-success" title="Mark as Hired">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                            </form>

                                        @else
                                            <button class="btn btn-sm btn-outline-secondary" disabled title="No actions available">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        @endif
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

{{-- 🔸 Set Control No. Modal --}}
<div class="modal fade" id="setCtrlModal" tabindex="-1" role="dialog" aria-labelledby="setCtrlModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg rounded">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title font-weight-bold" id="setCtrlModalLabel">
          <i class="fas fa-key mr-2"></i> Set Control Number
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form id="ctrlForm" method="POST" action="{{ route('setCtrlNo') }}">
        @csrf
        <div class="modal-body">
          <input type="hidden" name="id" id="ctrlAppId">
          <div class="form-group">
            <label for="ctrl_no">Control Number</label>
            <input type="text" name="ctrl_no" id="ctrl_no" class="form-control" placeholder="Enter Control Number" autocomplete="off" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning text-dark">
            <i class="fas fa-save"></i> Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- 🔸 Qualified (Interview Schedule) Modal --}}
<div class="modal fade" id="qualifyModal" tabindex="-1" role="dialog" aria-labelledby="qualifyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow rounded">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title font-weight-bold" id="qualifyModalLabel">
          <i class="fas fa-calendar-check mr-2"></i> Set Interview Schedule
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form method="POST" action="{{ route('updateStatus') }}">
        @csrf
        <input type="hidden" name="id" id="qualifyAppId">
        <input type="hidden" name="status" value="2">

        <div class="modal-body">
          <div class="form-group">
            <label for="interview_datetime">Interview Schedule <span class="text-danger">*</span></label>
            <input type="datetime-local" id="interview_datetime" name="interview_datetime" class="form-control" required>
            <small class="form-text text-muted">Example: September 16, 2025, at 2:00 PM</small>
          </div>

          <div class="form-group">
            <label for="venue">Venue <span class="text-danger">*</span></label>
            <textarea id="venue" name="venue" class="form-control" rows="2" required>Conference Room, Admin Building/Bidding Room/Accreditation/ Mini Hotel</textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times"></i> Cancel
          </button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check"></i> Confirm & Qualify
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- 🔸 Disqualification Reason Modal --}}
<div class="modal fade" id="dqModal" tabindex="-1" role="dialog" aria-labelledby="dqModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow rounded">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title font-weight-bold" id="dqModalLabel">
          <i class="fas fa-times-circle mr-2"></i> Disqualify Applicant
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form method="POST" action="{{ route('updateStatus') }}">
        @csrf
        <input type="hidden" name="id" id="dqAppId">
        <input type="hidden" name="status" value="3">

        <div class="modal-body">
          <div class="form-group">
            <label for="dqReason">Reason for Disqualification <span class="text-danger">*</span></label>
            <textarea name="reason" id="dqReason" class="form-control" rows="3" placeholder="Enter reason..." required></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times"></i> Cancel
          </button>
          <button type="submit" class="btn btn-danger">
            <i class="fas fa-check"></i> Confirm Disqualification
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Set Control No.
    document.querySelectorAll('.set-ctrl').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('ctrlAppId').value = btn.value;
        });
    });

    // Qualified modal
    document.querySelectorAll('.q-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('qualifyAppId').value = btn.dataset.appId;
        });
    });

    // Disqualified modal
    document.querySelectorAll('.dq-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('dqAppId').value = btn.dataset.appId;
        });
    });
});
</script>
@endsection
