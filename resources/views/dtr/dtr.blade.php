@extends('layouts.master')

@section('body')
@php
    $showEmployee = $guard == "web" || $acctstat == 1;
    $generated = isset($employee, $period, $date) && $employee;
    $selectedPeriod = $generated ? (int) $period : 1;
    $periodLabels = [1 => '1st half', 2 => '2nd half', 3 => 'Whole month'];
    $pdfUrl = $generated
        ? route('dtr-pdf', ['employee' => $employee->emp_ID, 'period' => $period, 'date' => $date, 'overtime' => $overtime, 'tardiness' => $tardiness ?? null])
        : null;
@endphp
<div class="container-fluid dash">
    <div class="dtr-head">
        <div>
            <h1>Daily Time Record</h1>
            <p>Generate the printable DTR of an employee for a pay period.</p>
        </div>
        @include('dtr.submenu')
    </div>

    <div class="dash-card">
        <div class="dash-card-header">
            <h5><i class="fas fa-sliders-h" style="color: var(--cpsu-green-600);"></i>Report options</h5>
        </div>
        <div class="dash-card-body">
            <form class="dtr-form" id="dtrForm" action="{{ route('dtrSearch') }}" method="POST">
                @csrf
                <input type="hidden" name="acctstat" value="{{ $acctstat }}">

                <div class="row">
                    @if($guard == "web")
                    <div class="col-lg-5 dtr-field">
                        <label class="dtr-label" for="employee">Employee</label>
                        <select class="form-control {{ (auth()->guard($guard)->user()->role == "employee") ? '' : 'select2' }}" name="employee" id="employee" style="width: 100%; {{ auth()->guard($guard)->user()->role == "employee" ? 'pointer-events: none;' : '' }}" required>
                            <option disabled selected>Select</option>
                            @if(auth()->guard($guard)->user()->role !== "employee")
                                @foreach($employeeall as $emp)
                                    <option value="{{ $emp->emp_ID }}" @if(isset($employee) && $employee && $emp->emp_ID == $employee->emp_ID) selected @endif>
                                        {{ $emp->lname }}
                                        {{ $emp->prefix }}
                                        {{ $emp->fname }}
                                        {{ isset($emp->mname) ?substr($emp->mname, 0, 1).'.' : '' }}
                                    </option>
                                @endforeach
                            @else
                                <option value="{{ $employeeall->emp_ID }}" selected>
                                    {{ $employeeall->lname }}
                                    {{ $employeeall->prefix }}
                                    {{ $employeeall->fname }}
                                    {{ isset($employeeall->mname) ?substr($employeeall->mname, 0, 1).'.' : '' }}
                                </option>
                            @endif
                        </select>
                    </div>
                    @elseif($acctstat == 1)
                    <div class="col-lg-5 dtr-field">
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

                    <div class="{{ $showEmployee ? 'col-lg-4 col-md-7' : 'col-md-7' }} dtr-field">
                        <span class="dtr-label" id="periodLabel">Period</span>
                        <div class="dtr-seg is-block" role="radiogroup" aria-labelledby="periodLabel">
                            @foreach($periodLabels as $value => $label)
                                <input type="radio" name="period" id="period{{ $value }}" value="{{ $value }}" {{ $selectedPeriod === $value ? 'checked' : '' }} required>
                                <label for="period{{ $value }}">{{ $label }}</label>
                            @endforeach
                        </div>
                    </div>

                    <div class="{{ $showEmployee ? 'col-lg-3 col-md-5' : 'col-md-5' }} dtr-field">
                        <label class="dtr-label" for="date">Month</label>
                        <input type="month" name="date" class="form-control" id="date" value="{{ isset($employee) ? $date : '' }}" required>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between pt-1" style="gap: 12px;">
                    <div class="dtr-checks">
                        <label class="dtr-check">
                            <input type="checkbox" value="1" name="overtime" {{ isset($employee) && $overtime == 1 ? 'checked' : '' }}>
                            <span>Include overtime</span>
                        </label>
                        @if(!empty($canTardiness))
                        <label class="dtr-check">
                            <input type="checkbox" value="1" name="tardiness" {{ isset($employee) && isset($tardiness) && $tardiness == 1 ? 'checked' : '' }}>
                            <span>Show late / undertime</span>
                        </label>
                        @endif
                    </div>
                    <button type="submit" class="dtr-generate">
                        <i class="fas fa-file-pdf mr-1"></i> Generate DTR
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
                    <span><strong>{{ $employee->lname }}, {{ $employee->fname }}</strong></span>
                    <span>{{ $periodLabels[$selectedPeriod] ?? '' }} &middot; {{ \Carbon\Carbon::createFromFormat('Y-m', $date)->format('F Y') }}</span>
                    <a href="{{ $pdfUrl }}" target="_blank" rel="noopener">Open in new tab <i class="fas fa-external-link-alt fa-xs"></i></a>
                </div>
            @endif
        </div>
        @if($generated)
            <div class="dtr-frame" id="dtrFrame">
                <div class="dtr-frame-loading"><i class="fas fa-spinner fa-spin"></i> Preparing the DTR...</div>
                <iframe src="{{ $pdfUrl }}" title="DTR preview" onload="this.parentNode.classList.add('is-loaded')"></iframe>
            </div>
        @else
            <div class="dtr-empty">
                <div class="dtr-empty-icon"><i class="far fa-calendar-alt"></i></div>
                <h6>No DTR generated yet</h6>
                <p>{{ $showEmployee ? 'Pick an employee, a period and a month' : 'Pick a period and a month' }}, then press <b>Generate DTR</b>. The printable copy shows up here.</p>
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

    document.getElementById('dtrForm').addEventListener('submit', function () {
        var btn = this.querySelector('.dtr-generate');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Generating...';
    });
</script>
@endsection
