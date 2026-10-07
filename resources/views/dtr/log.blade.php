@extends('layouts.master')

@section('body')
@php
    $showEmployee = $guard == "web" || $acctstat == 1;
    $generated = !empty($data);
    $logsUrl = $generated
        ? route('logDtrView', ['employeeId' => $data['employeeId'] ?? 0, 'dateFrom' => $data['dateFrom'] ?? null, 'dateTo' => $data['dateTo'] ?? null, 'overtime' => $data['overtime'] ?? null])
        : null;
@endphp
<div class="container-fluid dash">
    <div class="dtr-head">
        <div>
            <h1>Time Logs</h1>
            <p>Review the raw time-in and time-out logs of an employee for any date range.</p>
        </div>
        @include('dtr.submenu')
    </div>

    <div class="dash-card">
        <div class="dash-card-header">
            <h5><i class="fas fa-sliders-h" style="color: var(--cpsu-green-600);"></i>Report options</h5>
        </div>
        <div class="dash-card-body">
            <form class="dtr-form" id="logsForm" action="{{ route('dtrLogspost') }}" method="POST">
                @csrf
                <input type="hidden" name="acctstat" value="{{ $acctstat }}">

                <div class="row">
                    @if($guard == "web")
                        <div class="col-lg-6 dtr-field">
                            <label class="dtr-label" for="employee">Employee</label>
                            <select class="form-control {{ (auth()->guard($guard)->user()->role == "employee") ? '' : 'select2' }}" name="employee" id="employee" style="width: 100%; {{ auth()->guard($guard)->user()->role == "employee" ? 'pointer-events: none;' : '' }}" required>
                                <option disabled selected>Select</option>
                                @if(auth()->guard($guard)->user()->role !== "employee")
                                    @foreach($employeeall as $emp)
                                        <option value="{{ $emp->emp_ID }}" @if(($data != null) && $emp->emp_ID == $data['employeeId']) selected @endif>
                                            {{ strtoupper(ucwords($emp->lname)) }}
                                            {{ strtoupper(ucwords($emp->prefix)) }}
                                            {{ strtoupper(ucwords($emp->fname)) }}
                                            {{ strtoupper(ucwords($emp->mname)) }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="{{ $employeeall->emp_ID }}" selected>
                                        {{ strtoupper(ucwords($employeeall->lname)) }}
                                        {{ strtoupper(ucwords($employeeall->prefix)) }}
                                        {{ strtoupper(ucwords($employeeall->fname)) }}
                                        {{ strtoupper(ucwords($employeeall->mname)) }}
                                    </option>
                                @endif
                            </select>
                        </div>
                    @elseif($acctstat == 1)
                        <div class="col-lg-6 dtr-field">
                            <label class="dtr-label" for="employee">Employee</label>
                            <select class="form-control select2" name="employee" id="employee" style="width: 100%;" required>
                                <option disabled selected>Select</option>
                                @foreach($employeeall as $emp)
                                    <option value="{{ $emp->emp_ID }}" @if(isset($employee) && $employee && $emp->emp_ID == $employee->emp_ID) selected @endif>
                                        {{ $emp->lname }}
                                        {{ $emp->prefix }}
                                        {{ $emp->fname }}
                                        {{ isset($emp->mname) ?substr($emp->mname, 0, 1).'.' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="{{ $showEmployee ? 'col-lg-3' : 'col-lg-6' }} col-sm-6 dtr-field">
                        <label class="dtr-label" for="inc_date1">From</label>
                        <input type="date" name="date_from" class="form-control" id="inc_date1" value="{{ ($data != null) ? $data['dateFrom'] : '' }}" required>
                    </div>
                    <div class="{{ $showEmployee ? 'col-lg-3' : 'col-lg-6' }} col-sm-6 dtr-field">
                        <label class="dtr-label" for="inc_date2">To</label>
                        <input type="date" name="date_to" class="form-control" id="inc_date2" value="{{ ($data != null) ? $data['dateTo'] : '' }}" required>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between pt-1" style="gap: 12px;">
                    <div class="dtr-checks">
                        <label class="dtr-check">
                            <input type="checkbox" value="1" name="overtime" {{ ($data['overtime'] ?? 0) == 1 ? 'checked' : '' }}>
                            <span>Include overtime</span>
                        </label>
                    </div>
                    <button type="submit" class="dtr-generate">
                        <i class="fas fa-file-pdf mr-1"></i> Generate logs
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-card">
        <div class="dash-card-header">
            <h5><i class="fas fa-file-invoice" style="color: var(--cpsu-green-600);"></i>Preview</h5>
            @if($generated)
                <div class="dtr-preview-meta">
                    <span><strong>{{ \Carbon\Carbon::parse($data['dateFrom'])->format('M j, Y') }}</strong> to <strong>{{ \Carbon\Carbon::parse($data['dateTo'])->format('M j, Y') }}</strong></span>
                    <a href="{{ $logsUrl }}" target="_blank" rel="noopener">Open in new tab <i class="fas fa-external-link-alt fa-xs"></i></a>
                </div>
            @endif
        </div>
        @if($generated)
            <div class="dtr-frame" id="dtrFrame">
                <div class="dtr-frame-loading"><i class="fas fa-spinner fa-spin"></i> Preparing the logs...</div>
                <iframe src="{{ $logsUrl }}" title="Logs preview" onload="this.parentNode.classList.add('is-loaded')"></iframe>
            </div>
        @else
            <div class="dtr-empty">
                <div class="dtr-empty-icon"><i class="fas fa-fingerprint"></i></div>
                <h6>No logs generated yet</h6>
                <p>{{ $showEmployee ? 'Pick an employee and a date range' : 'Pick a date range' }}, then press <b>Generate logs</b>. The printable copy shows up here.</p>
            </div>
        @endif
    </div>
</div>
<script>
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };

    // Some PDF viewers never fire the iframe load event; don't leave the overlay up.
    setTimeout(function () {
        var frame = document.getElementById('dtrFrame');
        if (frame) frame.classList.add('is-loaded');
    }, 8000);

    document.getElementById('logsForm').addEventListener('submit', function () {
        var btn = this.querySelector('.dtr-generate');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Generating...';
    });
</script>
@endsection
