@php
    $isReports = request()->is('event/reports*');
@endphp
<nav class="dtr-seg" aria-label="Event pages">
    <a href="{{ route('eventIndex') }}" class="{{ $isReports ? '' : 'is-active' }}" @unless($isReports) aria-current="page" @endunless>
        <i class="fas fa-calendar-alt"></i> Calendar
    </a>
    <a href="{{ route('showReport') }}" class="{{ $isReports ? 'is-active' : '' }}" @if($isReports) aria-current="page" @endif>
        <i class="fas fa-file-pdf"></i> Attendance reports
    </a>
</nav>
