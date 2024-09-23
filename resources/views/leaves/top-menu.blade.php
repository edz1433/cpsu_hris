@php
    $leaveCreditsRoute = $guard == "web" ? route('leavesRead', $employee->id) : route('leavesReadEmp');
    $statusRoute = $guard == "web" ? route('leaveStatusEmp', $employee->id) : route('leaveStatus');
    $historyRoute = $guard == 'web' ? route('PDS', $employee->id) : route('empPDS');

    $isLeaveCreditsActive = request()->is('emp-leaves') || request()->is('leaves*');
    $isStatusActive = request()->is('emp-leaves/status*') || request()->is('emp-leaves/emp-status*');
@endphp

<div class="row">
    <div class="col-md-4">
        <a href="{{ $leaveCreditsRoute }}" class="nav-link mb-1 {{ $isLeaveCreditsActive ? 'bg-default' : 'bg-secondary' }}" style="border-radius: 5px;">
            <i class="pr-2 fas fa-id-card" style="width: 20px; margin-left: 3px;"></i> 
            <span class="text-light text-bold">{{ ($guard == "web") ? 'LEAVE CREDITS' : 'APPLICATION FORM'}}</span> 
            <span class="float-right pt-1 badge badge-light">0</span>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ $statusRoute }}" class="nav-link mb-1 {{ $isStatusActive ? 'bg-default' : 'bg-secondary' }}" style="border-radius: 5px;">
            <i class="pr-2 fas fa-stamp text-light" style="width: 20px; margin-left: 3px;"></i> 
            <span class="text-light text-bold">STATUS</span> 
            <span class="float-right pt-1 badge badge-light">0</span>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ $historyRoute }}" class="nav-link mb-1 bg-secondary" style="border-radius: 5px;">
            <i class="pr-2 fas fa-history" style="width: 20px; margin-left: 3px;"></i> 
            <span class="text-light text-bold">HISTORY</span> 
            <span class="float-right pt-1 badge badge-light">0</span>
        </a>
    </div>
</div>
