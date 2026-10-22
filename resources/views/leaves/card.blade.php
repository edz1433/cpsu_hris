@extends('layouts.master')

@section('body')
@include('leaves.style')
@php
    // The printable card covers whole months, so the range is picked
    // as a first and last month (Y-m), same as the DTR picks a month.
    $firstMonth = $firstMonth ?: now()->startOfYear()->format('Y-m');
    $from = $from ?: $firstMonth;
    $to = $to ?: now()->format('Y-m');

    $periodLabel = \Carbon\Carbon::createFromFormat('Y-m', $from)->format('F Y')
        . ' – ' . \Carbon\Carbon::createFromFormat('Y-m', $to)->format('F Y');
@endphp
<section class="content">
<div class="container-fluid dash">
    @include("leaves.page-head")

    <div class="dash-card">
        <div class="dash-card-header">
            <h5><i class="fas fa-sliders-h" style="color: var(--cpsu-green-600);"></i>Report options</h5>
        </div>
        <div class="dash-card-body">
            <form class="dtr-form" id="cardForm" method="GET" action="{{ $guard == 'web' ? route('leaveCard', ['id' => $employee->id]) : route('leaveCard') }}">
                <div class="row">
                    @if($guard == "web")
                    <div class="col-lg-5 dtr-field">
                        <label class="dtr-label" for="cardEmployee">Employee</label>
                        <select class="form-control select2" id="cardEmployee" style="width: 100%;" required>
                            <option disabled selected>Select</option>
                            @foreach($emplalls as $emp)
                                <option value="{{ $emp->id }}" @if($employee->id == $emp->id) selected @endif>
                                    {{ $emp->lname }}
                                    {{ $emp->prefix }}
                                    {{ $emp->fname }}
                                    {{ isset($emp->mname) ? substr($emp->mname, 0, 1).'.' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="{{ $guard == 'web' ? 'col-lg-3 col-md-5' : 'col-md-5' }} dtr-field">
                        <label class="dtr-label" for="cardFrom">From month</label>
                        <input type="month" name="from" class="form-control" id="cardFrom" value="{{ $from }}" required>
                    </div>

                    <div class="{{ $guard == 'web' ? 'col-lg-3 col-md-5' : 'col-md-5' }} dtr-field">
                        <label class="dtr-label" for="cardTo">To month</label>
                        <input type="month" name="to" class="form-control" id="cardTo" value="{{ $to }}" required>
                    </div>
                </div>

                <div class="d-flex justify-content-end pt-1">
                    <button type="submit" class="dtr-generate">
                        <i class="fas fa-file-pdf mr-1"></i> Generate Leave Card
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
                    <span>{{ $periodLabel }}</span>
                    <a href="{{ $pdfUrl }}" target="_blank" rel="noopener">Open in new tab <i class="fas fa-external-link-alt fa-xs"></i></a>
                </div>
            @endif
        </div>
        @if($generated)
            <div class="dtr-frame" id="cardFrame">
                <div class="dtr-frame-loading"><i class="fas fa-spinner fa-spin"></i> Preparing the leave card...</div>
                <iframe src="{{ $pdfUrl }}" title="Leave card preview" onload="this.parentNode.classList.add('is-loaded')"></iframe>
            </div>
        @else
            <div class="dtr-empty">
                <div class="dtr-empty-icon"><i class="far fa-calendar-alt"></i></div>
                <h6>No leave card generated yet</h6>
                <p>Pick a date range, then press <b>Generate Leave Card</b>. The printable copy shows up here.</p>
            </div>
        @endif
    </div>
</div>
</section>
<script>
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };

    // The employee id travels in the URL, not the query string.
    @if($guard == "web")
    document.getElementById('cardEmployee').addEventListener('change', function () {
        document.getElementById('cardForm').action =
            '{{ route('leaveCard', ['id' => ':id']) }}'.replace(':id', this.value);
    });
    @endif

    // Some PDF viewers never fire the iframe load event; don't leave the overlay up.
    setTimeout(function () {
        var frame = document.getElementById('cardFrame');
        if (frame) frame.classList.add('is-loaded');
    }, 8000);

    document.getElementById('cardForm').addEventListener('submit', function () {
        var btn = this.querySelector('.dtr-generate');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Generating...';
    });
</script>
@endsection
