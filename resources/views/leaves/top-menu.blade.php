@php
    $leaveCreditsRoute = $guard == "web" ? route('leavesRead', $employee->id) : route('leavesReadEmp');
    $statusRoute = $guard == "web" ? route('leaveStatus', $employee->id) : route('leaveStatus');
    $historyRoute = $guard == 'web' ? route('historyRead', $employee->id) : route('historyRead');

    $isLeaveCreditsActive = request()->is('leave') || request()->is('leaves*');
    $isStatusActive = request()->is('leave/status') || request()->is('leave/status/*') || request()->is('leaves/status*');
    $isHistoryActive = request()->is('leave/history*') || request()->is('leaves/history');
@endphp

<nav class="dtr-seg" aria-label="Leave pages">
    <a href="{{ $leaveCreditsRoute }}" class="{{ $isLeaveCreditsActive ? 'is-active' : '' }}" @if($isLeaveCreditsActive) aria-current="page" @endif>
        <i class="fas {{ $guard == 'web' ? 'fa-id-card' : 'fa-file-signature' }}"></i> {{ $guard == "web" ? 'Leave credits' : 'Application form' }}
    </a>
    <a href="{{ $statusRoute }}" class="{{ $isStatusActive ? 'is-active' : '' }}" @if($isStatusActive) aria-current="page" @endif>
        <i class="fas fa-stamp"></i> Status
    </a>
    <a href="{{ $historyRoute }}" class="{{ $isHistoryActive ? 'is-active' : '' }}" @if($isHistoryActive) aria-current="page" @endif>
        <i class="fas fa-history"></i> History
    </a>
</nav>
