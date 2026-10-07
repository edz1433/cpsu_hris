@extends('layouts.master')

@section('body')
@php
  // Preset label => colour class saved with the event (same classes as the swatches).
  $presets = [
    'Academic Council' => 'bg-primary',
    'Admin Council' => 'bg-info',
    'Convocation' => 'bg-warning',
    'Trainings & Seminar' => 'bg-success',
    'Orientation' => 'bg-danger',
    'Meeting' => 'bg-secondary',
  ];
  $swatches = [
    'bg-primary' => ['text-primary', 'Blue'],
    'bg-info' => ['text-info', 'Teal'],
    'bg-warning' => ['text-warning', 'Yellow'],
    'bg-success' => ['text-success', 'Green'],
    'bg-danger' => ['text-danger', 'Red'],
    'bg-secondary' => ['text-secondary', 'Gray'],
  ];
@endphp
<div class="container-fluid dash">
  <div class="dtr-head">
    <div>
      <h1>Events</h1>
      <p>University events shown on every employee's dashboard calendar.</p>
    </div>
    @include('events.submenu')
  </div>

  <div class="row">
    <div class="col-xl-3 col-lg-4">
      <div class="dash-card">
        <div class="dash-card-header">
          <h5><i class="fas fa-bolt" style="color: var(--cpsu-green-600);"></i>Quick add</h5>
        </div>
        <div class="dash-card-body">
          <p class="ev-hint">Drag a preset onto a date. The event form opens with the title and colour filled in.</p>
          <div id="external-events" class="ev-presets">
            @foreach($presets as $label => $color)
              <div class="external-event ev-preset ev-tone-{{ substr($color, 3) }}" data-color="{{ $color }}" title="Drag onto the calendar">
                <span class="ev-dot" aria-hidden="true"></span>{{ $label }}
              </div>
            @endforeach
          </div>
        </div>
        <div class="ev-howto">
          <h6>On the calendar</h6>
          <ul>
            <li><i class="fas fa-mouse-pointer"></i><span><b>Click a day</b> to add an event on it</span></li>
            <li><i class="fas fa-arrows-alt-h"></i><span><b>Drag across days</b> for a multi-day event</span></li>
            <li><i class="fas fa-pen"></i><span><b>Click an event</b> to edit or delete it</span></li>
            <li><i class="fas fa-arrows-alt"></i><span><b>Drag an event</b> to move it to another date</span></li>
          </ul>
        </div>
      </div>
    </div>

    <div class="col-xl-9 col-lg-8">
      <div class="dash-card dash-calendar">
        <div class="dash-card-body">
          <div id="calendar" class="bg-white"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Create Event Modal (opened by clicking / dragging the calendar) -->
<div class="modal fade ev-modal" id="createEventModal" tabindex="-1" role="dialog" aria-labelledby="createEventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form class="modal-content dtr-form" method="POST" action="{{ route('eventCreate') }}">
      @csrf
      <input type="hidden" name="user_id" value="{{ Auth::user()->id }}">
      <input type="hidden" name="bg_color" id="create_bg_color" value="bg-primary">

      <div class="modal-header">
        <h5 class="modal-title" id="createEventModalLabel"><i class="fas fa-calendar-plus"></i> New event</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <div class="dtr-field">
          <label class="dtr-label" for="create_title">Event title</label>
          <input type="text" class="form-control" name="title" id="create_title" required>
        </div>
        <div class="dtr-field">
          <label class="dtr-label" for="create_venue">Venue</label>
          <input type="text" class="form-control" name="venue" id="create_venue" required>
        </div>
        <div class="dtr-field">
          <label class="dtr-label" for="create_org_dept">Organizing department/s</label>
          <input type="text" class="form-control" name="org_dept" id="create_org_dept" required>
        </div>
        <div class="form-row">
          <div class="col-6 dtr-field">
            <label class="dtr-label" for="create_campus">Campus</label>
            <select class="form-control" name="campus_id" id="create_campus" required>
              <option value="0">All</option>
              @foreach ($campus as $cp)
                <option value="{{ $cp->id }}">{{ $cp->campus_name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 dtr-field">
            <label class="dtr-label" for="create_emp_status">Employee status</label>
            <select class="form-control" name="emp_status" id="create_emp_status" required>
              <option value="0">All</option>
              @foreach ($status as $st)
                <option value="{{ $st->id }}">{{ $st->status_name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="col-6 dtr-field">
            <label class="dtr-label" for="create_start">Starts</label>
            <input type="datetime-local" class="form-control" name="start" id="create_start" required>
          </div>
          <div class="col-6 dtr-field">
            <label class="dtr-label" for="create_end">Ends <span class="font-weight-normal">(optional)</span></label>
            <input type="datetime-local" class="form-control" name="end" id="create_end">
          </div>
        </div>
        <div>
          <span class="dtr-label">Colour on the calendar</span>
          <ul class="color-picker" id="createEventColor" data-target="create_bg_color">
            @foreach($swatches as $color => [$textClass, $name])
              <li><a href="#" class="{{ $textClass }} color-swatch {{ $color === 'bg-primary' ? 'selected' : '' }}" data-color="{{ $color }}" title="{{ $name }}" aria-label="{{ $name }}"></a></li>
            @endforeach
          </ul>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="lv-btn" data-dismiss="modal">Cancel</button>
        <button type="submit" class="dtr-generate"><i class="fas fa-save mr-1"></i> Save event</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit / Delete Event Modal -->
<div class="modal fade ev-modal" id="editEventModal" tabindex="-1" role="dialog" aria-labelledby="editEventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form class="modal-content dtr-form" method="POST" action="{{ route('eventUpdateSave') }}">
      @csrf
      <input type="hidden" name="id" id="edit_event_id">
      <input type="hidden" name="bg_color" id="edit_bg_color" value="bg-primary">

      <div class="modal-header">
        <h5 class="modal-title" id="editEventModalLabel"><i class="fas fa-calendar-check"></i> Edit event</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <div class="dtr-field">
          <label class="dtr-label" for="edit_title">Event title</label>
          <input type="text" class="form-control" name="title" id="edit_title" required>
        </div>
        <div class="dtr-field">
          <label class="dtr-label" for="edit_venue">Venue</label>
          <input type="text" class="form-control" name="venue" id="edit_venue" required>
        </div>
        <div class="dtr-field">
          <label class="dtr-label" for="edit_org_dept">Organizing department/s</label>
          <input type="text" class="form-control" name="org_dept" id="edit_org_dept" required>
        </div>
        <div class="form-row">
          <div class="col-6 dtr-field">
            <label class="dtr-label" for="edit_start">Starts</label>
            <input type="datetime-local" class="form-control" name="start" id="edit_start" required>
          </div>
          <div class="col-6 dtr-field">
            <label class="dtr-label" for="edit_end">Ends <span class="font-weight-normal">(optional)</span></label>
            <input type="datetime-local" class="form-control" name="end" id="edit_end">
          </div>
        </div>
        <div>
          <span class="dtr-label">Colour on the calendar</span>
          <ul class="color-picker" id="editEventColor" data-target="edit_bg_color">
            @foreach($swatches as $color => [$textClass, $name])
              <li><a href="#" class="{{ $textClass }} color-swatch" data-color="{{ $color }}" title="{{ $name }}" aria-label="{{ $name }}"></a></li>
            @endforeach
          </ul>
        </div>
      </div>

      <div class="modal-footer justify-content-between">
        <button type="button" class="lv-btn is-danger" id="deleteEventBtn" data-id="">
          <i class="fas fa-trash"></i> Delete
        </button>
        <div class="d-flex" style="gap: 8px;">
          <button type="button" class="lv-btn" data-dismiss="modal">Cancel</button>
          <button type="submit" class="dtr-generate"><i class="fas fa-save mr-1"></i> Save changes</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  (function () {
    var deleteUrlBase = "{{ url('event/delete') }}";
    var csrf = document.querySelector('meta[name="csrf-token"]');
    csrf = csrf ? csrf.getAttribute('content') : '';

    function selectSwatch(picker, color) {
      var target = document.getElementById(picker.getAttribute('data-target'));
      var swatch = picker.querySelector('.color-swatch[data-color="' + color + '"]');
      if (!target || !swatch) return;
      target.value = color;
      picker.querySelectorAll('.color-swatch').forEach(function (s) { s.classList.remove('selected'); });
      swatch.classList.add('selected');
    }

    document.addEventListener('click', function (e) {
      var swatch = e.target.closest('.color-picker .color-swatch');
      if (swatch) {
        e.preventDefault();
        selectSwatch(swatch.closest('.color-picker'), swatch.getAttribute('data-color'));
      }
    });

    // A dragged-in preset brings its colour; a click on the calendar starts from blue.
    // The calendar script sets window._pendingDropEvent before opening this modal.
    window.addEventListener('load', function () {
      if (!window.jQuery) return;
      jQuery('#createEventModal').on('show.bs.modal', function () {
        var color = 'bg-primary';
        var dropped = window._pendingDropEvent;
        if (dropped) {
          document.querySelectorAll('#external-events .external-event').forEach(function (el) {
            if (el.innerText.trim() === (dropped.title || '').trim()) color = el.getAttribute('data-color');
          });
        }
        selectSwatch(document.getElementById('createEventColor'), color);
      });
    });

    var deleteBtn = document.getElementById('deleteEventBtn');
    if (deleteBtn) {
      deleteBtn.addEventListener('click', function () {
        var id = this.getAttribute('data-id');
        if (!id) return;

        var doDelete = function () {
          fetch(deleteUrlBase + '/' + id, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf }
          })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (data.status === 200) {
                window.location.reload();
              } else if (window.toastr) {
                toastr.error(data.message || 'Failed to delete event.');
              }
            })
            .catch(function () { if (window.toastr) toastr.error('Failed to delete event.'); });
        };

        if (window.Swal) {
          Swal.fire({
            title: 'Delete this event?',
            text: 'This will remove the event and its attendance logs. This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete it'
          }).then(function (result) { if (result.isConfirmed) doDelete(); });
        } else if (window.confirm('Delete this event and its attendance logs?')) {
          doDelete();
        }
      });
    }
  })();
</script>

@endsection
