@extends('layouts.master')

@section('body')
@php
    $isEmployeeRole = auth()->guard($guard)->user()->role == "employee";
    $generated = request()->isMethod('post') && isset($employeeId, $month);
    $pdfUrl = $generated ? route('pdfTirednes', ['employeeId' => $employeeId, 'month' => $month]) : null;
    $monthLabel = $month ? \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') : null;
    $whoLabel = $employee ? trim($employee->lname . ', ' . $employee->fname) : 'All employees';
@endphp
<div class="container-fluid dash">
    <div class="dtr-head">
        <div>
            <h1>Tardiness &amp; Undertime</h1>
            <p>Monthly late and undertime totals, for one employee or everyone.</p>
        </div>
    </div>

    <div class="dash-card">
        <div class="dash-card-header">
            <h5><i class="fas fa-sliders-h" style="color: var(--cpsu-green-600);"></i>Report options</h5>
        </div>
        <div class="dash-card-body">
            <form class="dtr-form" id="tardinessForm" action="{{ route('tirednessSearch') }}" method="POST">
                @csrf
                <div class="row align-items-end">
                    <div class="col-lg-6 dtr-field">
                        <label class="dtr-label" for="employee">Employee</label>
                        <select class="form-control {{ $isEmployeeRole ? '' : 'select2' }}" name="employee" id="employee" style="width: 100%; {{ $isEmployeeRole ? 'pointer-events: none;' : '' }}" required>
                            <option value="0" selected>All employees</option>
                            @if(!$isEmployeeRole)
                                @foreach($employeeall as $emp)
                                    <option value="{{ $emp->emp_ID }}" @if(isset($employee) && $employee && $emp->emp_ID == $employee->emp_ID) selected @endif>
                                        {{ $emp->lname }} {{ $emp->prefix }} {{ $emp->fname }} {{ isset($emp->mname) ? substr($emp->mname, 0, 1).'.' : '' }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 dtr-field">
                        <label class="dtr-label" for="date">Month</label>
                        <input type="month" name="month" class="form-control" id="date" value="{{ ($month !== null) ? $month : date('Y-m') }}" required>
                    </div>
                    <div class="col-lg-3 col-md-6 dtr-field">
                        <button type="submit" class="dtr-generate w-100">
                            <i class="fas fa-file-pdf mr-1"></i> Generate report
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-card">
        <div class="dash-card-header">
            <h5><i class="fas fa-file-invoice" style="color: var(--cpsu-green-600);"></i>Preview</h5>
            @if($generated)
                <div class="dtr-preview-meta">
                    <span><strong>{{ $whoLabel }}</strong></span>
                    <span>{{ $monthLabel }}</span>
                    <a href="{{ $pdfUrl }}" target="_blank" rel="noopener">Open in new tab <i class="fas fa-external-link-alt fa-xs"></i></a>
                </div>
            @endif
        </div>
        @if($generated)
            <div class="dtr-frame" id="tardinessFrame">
                <div class="dtr-frame-loading"><i class="fas fa-spinner fa-spin"></i> Preparing the report...</div>
                <iframe src="{{ $pdfUrl }}" title="Tardiness and undertime report" onload="this.parentNode.classList.add('is-loaded')"></iframe>
            </div>
        @else
            <div class="dtr-empty">
                <div class="dtr-empty-icon"><i class="fas fa-hourglass-half"></i></div>
                <h6>No report generated yet</h6>
                <p>Pick an employee (or leave it on <b>All employees</b>) and a month, then press <b>Generate report</b>. The printable copy shows up here.</p>
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
        var frame = document.getElementById('tardinessFrame');
        if (frame) frame.classList.add('is-loaded');
    }, 8000);

    document.getElementById('tardinessForm').addEventListener('submit', function () {
        var btn = this.querySelector('.dtr-generate');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Generating...';
    });
</script>
@endsection
