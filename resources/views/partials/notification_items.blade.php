@php 
$leaveTypes = [
    1 => 'Vacation Leave',
    2 => 'Mandatory/Forced Leave',
    3 => 'Sick Leave',
    4 => 'Maternity Leave',
    5 => 'Paternity Leave',
    6 => 'Special Privilege Leave',
    7 => 'Solo Parent Leave',
    8 => 'Study Leave',
    9 => '10-Day VAWC Leave',
    10 => 'Rehabilitation Privilege',
    11 => 'Special Leave Benefits for Women',
    12 => 'Special Emergency (Calamity) Leave',
    13 => 'Adoption Leave',
    14 => 'Others'
];
@endphp

@foreach ($notifications as $notif)
@php 
    $timeDifference = $notif->notif_created_at 
        ? \Carbon\Carbon::parse($notif->notif_created_at)->timezone('Asia/Manila')->diffForHumans() 
        : ''; 
    $remarks = null;
@endphp

@switch($notif->module)
    @case('leave')
        @php
            $action = $notif->category == 1 ? "is applying for" : "is awaiting approval for";
            $remarks = "{$action} " . strtolower($leaveTypes[$notif->leave_type] ?? '') . " (Application No: #{$notif->transnum})";
        @endphp
        <a href="{{ route('leaveStatus', $notif->leave_emp_id) }}" class="dropdown-item d-flex align-items-center">
            <div class="mr-3">
                <img src="{{ asset('Profile/Employee/'.$notif->leave_emp_profile) }}" class="img-circle" alt="User Image" width="40" height="40">
            </div>
            <div>
                <p class="mb-0">
                    <strong>{{ ucwords(strtolower($notif->leave_emp_fullname)) }}</strong> {{ $remarks }}
                </p>
                <span class="{{ $notif->notifstat == 0 ? 'text-primary font-weight-bold' : 'text-muted' }} text-sm">
                    {{ $timeDifference }}
                </span>
            </div>
        </a>
        @break
    
    @case('pds')
        @switch($notif->category)
            @case(1)
                    @php
                        $remarks = "has submitted new eligibility.";
                        $profile = $notif->pds_emp_eligi_profile;
                        $fullname = $notif->pds_emp_eligi_fullname;
                    @endphp
                @break
            @case(2)
                    @php
                        $remarks = "has submitted new work experience.";
                        $profile = $notif->pds_emp_workexp_profile;
                        $fullname = $notif->pds_emp_workexp_fullname;
                    @endphp
                @break
            @case(3)
                    @php
                        $remarks = "has submitted new voluntary works.";
                        $profile = $notif->pds_emp_volworks_profile;
                        $fullname = $notif->pds_emp_volworks_fullname;
                    @endphp
                @break
            @case(4)
                    @php
                        $remarks = "has submitted new Learning and Development.";
                        $profile = $notif->pds_emp_learndev_profile;
                        $fullname = $notif->pds_emp_learndev_fullname;
                    @endphp
                @break
            @break
        @endswitch

        <a href="{{ route('eligibility', $notif->pds_emp_eligi_id) }}" class="dropdown-item d-flex align-items-center">
            <div class="mr-3">
                <img src="{{ asset('Profile/Employee/'.$profile) }}" class="img-circle" alt="User Image" width="40" height="40">
            </div>
            <div>
                <p class="mb-0">
                    <strong>{{ ucwords(strtolower($fullname)) }}</strong> {{ $remarks }}
                </p>
                <span class="{{ $notif->notifstat == 0 ? 'text-primary font-weight-bold' : 'text-muted' }} text-sm">
                    {{ $timeDifference }}
                </span>
            </div>
        </a>
    @break
@endswitch

<div class="dropdown-divider"></div>
@endforeach
