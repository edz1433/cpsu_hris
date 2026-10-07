@extends('layouts.master')

@section('body')
@php
  $generated = isset($eventid, $campusid, $statusid);
  $reportUrl = $generated
      ? route('reportGenrate', ['eventid' => $eventid, 'campusid' => $campusid, 'statusid' => $statusid])
      : null;
  $chosenEvent = $generated ? $events->firstWhere('id', $eventid) : null;
  $chosenCampus = $generated && $campusid != 0 ? optional($campus->firstWhere('id', $campusid))->campus_name : 'All campuses';
  $chosenStatus = $generated && $statusid != 0 ? optional($status->firstWhere('id', $statusid))->status_name : 'All employee statuses';
@endphp
<div class="container-fluid dash">
  <div class="dtr-head">
    <div>
      <h1>Event Attendance Reports</h1>
      <p>Print who attended an event, filtered by campus and employee status.</p>
    </div>
    @include('events.submenu')
  </div>

  <div class="dash-card">
    <div class="dash-card-header">
      <h5><i class="fas fa-sliders-h" style="color: var(--cpsu-green-600);"></i>Report options</h5>
    </div>
    <div class="dash-card-body">
      <form class="dtr-form" id="eventReportForm" action="{{ route('searchReport') }}" method="POST">
        @csrf
        <div class="row align-items-end">
          <div class="col-lg-4 dtr-field">
            <label class="dtr-label" for="report_event">Event</label>
            <select class="form-control select2" name="eventid" id="report_event" style="width: 100%;" required>
              @foreach($events as $event)
                <option value="{{ $event->id }}" @if($generated && $event->id == $eventid) selected @endif>
                  {{ ucfirst($event->title) }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-lg-3 col-sm-6 dtr-field">
            <label class="dtr-label" for="report_campus">Campus</label>
            <select class="form-control select2 update-field" name="campusid" id="report_campus" style="width: 100%;" required>
              <option value="0" @if(isset($campusid) && $campusid == 0) selected @endif>All</option>
              @foreach ($campus as $cp)
                <option value="{{ $cp->id }}" @if(isset($campusid) && $campusid == $cp->id) selected @endif>
                  {{ $cp->campus_name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-lg-3 col-sm-6 dtr-field">
            <label class="dtr-label" for="report_status">Employee status</label>
            <select class="form-control select2 update-field" name="statusid" id="report_status" style="width: 100%;" required>
              <option value="0" @if(isset($statusid) && $statusid == 0) selected @endif>All</option>
              @foreach ($status as $st)
                <option value="{{ $st->id }}" @if(isset($statusid) && $statusid == $st->id) selected @endif>
                  {{ $st->status_name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-lg-2 dtr-field">
            <button type="submit" class="dtr-generate w-100">
              <i class="fas fa-file-pdf mr-1"></i> Generate
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
          @if($chosenEvent)<span><strong>{{ ucfirst($chosenEvent->title) }}</strong></span>@endif
          <span>{{ $chosenCampus }} &middot; {{ $chosenStatus }}</span>
          <a href="{{ $reportUrl }}" target="_blank" rel="noopener">Open in new tab <i class="fas fa-external-link-alt fa-xs"></i></a>
        </div>
      @endif
    </div>
    @if($generated)
      <div class="dtr-frame" id="dtrFrame">
        <div class="dtr-frame-loading"><i class="fas fa-spinner fa-spin"></i> Preparing the report...</div>
        <iframe src="{{ $reportUrl }}" title="Attendance report preview" onload="this.parentNode.classList.add('is-loaded')"></iframe>
      </div>
    @else
      <div class="dtr-empty">
        <div class="dtr-empty-icon"><i class="fas fa-clipboard-check"></i></div>
        <h6>No report generated yet</h6>
        <p>Pick an event, then press <b>Generate</b>. Leave campus and status on All to include everyone.</p>
      </div>
    @endif
  </div>
</div>
<script>
  // Some PDF viewers never fire the iframe load event; don't leave the overlay up.
  setTimeout(function () {
    var frame = document.getElementById('dtrFrame');
    if (frame) frame.classList.add('is-loaded');
  }, 8000);

  document.getElementById('eventReportForm').addEventListener('submit', function () {
    var btn = this.querySelector('.dtr-generate');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Generating...';
  });
</script>
@endsection
