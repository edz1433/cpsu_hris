<nav class="dtr-seg" aria-label="Time record reports">
    <a href="{{ route('dtr-read') }}" class="{{ request()->is('dtr') ? 'is-active' : '' }}" @if(request()->is('dtr')) aria-current="page" @endif>
        <i class="fas fa-clock"></i> DTR
    </a>
    <a href="{{ route('dtrLogs') }}" class="{{ request()->is('dtr/dtr-logs') ? 'is-active' : '' }}" @if(request()->is('dtr/dtr-logs')) aria-current="page" @endif>
        <i class="fas fa-list-ul"></i> Logs
    </a>
</nav>
