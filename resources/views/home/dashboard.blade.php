@extends('layouts.master')

@section('body')
<style>
    .bg-white {
        border-radius: 15px;
    }
    .icon{
        position: absolute;
        top: 37px !important;
        right: 5px;
    }
    .border-radius{
      border-radius: 8px !important;
      width: 40px !important;
      height: 40px !important;
    } 
</style>
@if($guard == 'web')
  @include('home.modal')
@endif
@if($guard == 'employee')
@php
    $profileUrl = asset('Profile/Employee/' . $employee->profile);
    $profilePath = public_path('Profile/Employee/' . $employee->profile);
    $profileImage = file_exists($profilePath) && $employee->profile ? $profileUrl : asset('Profile/Employee/default.png');
    $fullName = trim(ucwords(strtolower($employee->fname . ' ' . $employee->lname)));
    $todayLabel = now('Asia/Manila')->format('F j, Y');
@endphp
<style>
    .employee-dashboard {
        color: #20312b;
    }
    .employee-hero {
        background: linear-gradient(135deg, #f7fbf8 0%, #e7f3ec 48%, #fff7df 100%);
        border: 1px solid rgba(24, 119, 68, .12);
        border-radius: 8px;
        padding: 22px;
        margin-bottom: 18px;
    }
    .employee-avatar {
        width: 78px;
        height: 78px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #fff;
        box-shadow: 0 8px 24px rgba(0,0,0,.08);
    }
    .metric-card,
    .action-card {
        background: #fff;
        border: 1px solid #e7ece9;
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(16,40,28,.05), 0 1px 3px rgba(16,40,28,.06);
        min-height: 112px;
    }
    .metric-card .text-muted:first-child {
        font-size: 13px;
        font-weight: 600;
    }
    .metric-card h4 {
        font-size: 26px;
        font-weight: 700;
    }
    .employee-dashboard .card {
        border: 1px solid #e7ece9;
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(16,40,28,.05), 0 1px 3px rgba(16,40,28,.06);
        overflow: hidden;
    }
    .employee-dashboard .card-header {
        border-bottom: 1px solid #e7ece9;
    }
    .metric-card .icon-wrap {
        width: 42px;
        height: 42px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eaf7f0;
        color: #187744;
    }
    .quick-action {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px;
        border-radius: 8px;
        color: #20312b;
        border: 1px solid #e7ece9;
        transition: all .15s ease;
    }
    .quick-action:hover {
        color: #187744;
        border-color: rgba(24, 119, 68, .35);
        background: #f7fbf8;
    }
    .quick-action i {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fff4d3;
        color: #8b6b00;
    }
    .dashboard-table td {
        vertical-align: middle;
    }
    .punch-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .punch-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 8px;
        border-radius: 999px;
        background: #f4f7f5;
        border: 1px solid #e2e9e5;
        font-size: 12px;
        white-space: nowrap;
    }
    .punch-pill strong {
        color: #187744;
    }
    .punch-pill.out strong {
        color: #9a5b00;
    }
    .punch-pill.ot strong {
        color: #6d4cc2;
    }
    .session-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(66px, 1fr));
        gap: 6px;
    }
    .session-cell {
        background: #f9fbfa;
        border: 1px solid #e2e9e5;
        border-radius: 8px;
        padding: 6px;
        text-align: center;
        min-height: 52px;
    }
    .session-cell span {
        display: block;
        color: #738078;
        font-size: 11px;
        text-transform: uppercase;
    }
    .session-cell strong {
        display: block;
        color: #20312b;
        font-size: 13px;
        margin-top: 2px;
    }
    .official-hours-note {
        color: #738078;
        font-size: 12px;
        line-height: 1.4;
    }
    .date-filter {
        background: #fff;
        border: 1px solid #e7ece9;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 18px;
        box-shadow: 0 8px 20px rgba(31,49,43,.04);
    }
    .date-filter .date-shell {
        position: relative;
    }
    .date-filter .date-shell i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #187744;
        z-index: 2;
    }
    .date-filter .date-input {
        height: 42px;
        border-radius: 8px;
        border-color: #dfe8e3;
        padding-left: 42px;
        background: #f9fbfa;
        cursor: pointer;
    }
</style>
<div class="container-fluid employee-dashboard dash">
    <div class="dash-hero">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <img src="{{ $profileImage }}" class="dash-avatar mr-3" alt="Profile Image">
                <div>
                    <div class="dash-eyebrow">{{ $todayLabel }}</div>
                    <h3>Welcome, {{ $fullName }}</h3>
                    <div class="dash-sub">
                        {{ $employee->position ?: 'Employee' }}
                        @if($employee->emp_ID)
                            <span class="mx-2">&middot;</span>ID {{ $employee->emp_ID }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('dashboard') }}" class="date-filter">
        <div class="row align-items-end">
            <div class="col-12">
                <label class="text-muted mb-1" for="dashboard_date_range">Date Range</label>
                <div class="date-shell">
                    <i class="fas fa-calendar-alt"></i>
                    <input type="text" id="dashboard_date_range" class="form-control date-input" value="{{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}" readonly>
                </div>
                <input type="hidden" id="date_from" name="date_from" value="{{ $dateFrom }}">
                <input type="hidden" id="date_to" name="date_to" value="{{ $dateTo }}">
            </div>
        </div>
    </form>

    <section class="content">
        <div class="row">
            @if($isRegularEmployee)
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted">Leave Records</div>
                            <h4 class="mb-0">{{ number_format($leaveCount) }}</h4>
                        </div>
                        <span class="icon-wrap"><i class="fas fa-calendar-check"></i></span>
                    </div>
                    <small class="text-muted">Total applications filed</small>
                </div>
            </div>
            @endif
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted">Total Late</div>
                            <h4 class="mb-0">{{ $totalLate }}</h4>
                        </div>
                        <span class="icon-wrap"><i class="fas fa-business-time"></i></span>
                    </div>
                    <small class="text-muted">For selected range</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted">Total Undertime</div>
                            <h4 class="mb-0">{{ $totalUndertime }}</h4>
                        </div>
                        <span class="icon-wrap"><i class="fas fa-hourglass-half"></i></span>
                    </div>
                    <small class="text-muted">For selected range</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted">Service</div>
                            <h4 class="mb-0">{{ is_null($serviceYears) ? '--' : $serviceYears . ' yr' . ($serviceYears == 1 ? '' : 's') }}</h4>
                        </div>
                        <span class="icon-wrap"><i class="fas fa-id-badge"></i></span>
                    </div>
                    <small class="text-muted">{{ $employee->date_hired ? 'Since ' . \Carbon\Carbon::parse($employee->date_hired)->format('M d, Y') : 'Date hired not set' }}</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h3 class="card-title font-weight-bold">Campus Events</h3>
                    </div>
                    <div class="card-body" style="background-color: #f4f7f5;">
                        <div id="external-events"></div>
                        <div id="calendar" class="bg-white"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h3 class="card-title font-weight-bold">Recent DTR</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm dashboard-table mb-0">
                            <tbody>
                                @forelse($recentDtrs as $dtr)
                                    <tr>
                                        <td>
                                            <strong>{{ \Carbon\Carbon::parse($dtr->date)->format('M d') }}</strong>
                                            <div class="official-hours-note">
                                                AM {{ $dtr->official_schedule['am'] }}<br>
                                                PM {{ $dtr->official_schedule['pm'] }}
                                            </div>
                                        </td>
                                        <td colspan="2">
                                            <div class="session-grid">
                                                <div class="session-cell">
                                                    <span>AM In</span>
                                                    <strong>{{ $dtr->daily_punches['am_in'] ?: '--' }}</strong>
                                                </div>
                                                <div class="session-cell">
                                                    <span>AM Out</span>
                                                    <strong>{{ $dtr->daily_punches['am_out'] ?: '--' }}</strong>
                                                </div>
                                                <div class="session-cell">
                                                    <span>PM In</span>
                                                    <strong>{{ $dtr->daily_punches['pm_in'] ?: '--' }}</strong>
                                                </div>
                                                <div class="session-cell">
                                                    <span>PM Out</span>
                                                    <strong>{{ $dtr->daily_punches['pm_out'] ?: '--' }}</strong>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted p-3">No DTR records yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="action-card p-3 mb-3">
                    <h5 class="font-weight-bold mb-3">Quick Actions</h5>
                    <a class="quick-action mb-2" href="{{ route('empPDS') }}">
                        <i class="fas fa-clipboard"></i>
                        <span>Open PDS</span>
                    </a>
                    @if($isRegularEmployee)
                        <a class="quick-action mb-2" href="{{ route('leavesReadEmp') }}">
                            <i class="fas fa-calendar-plus"></i>
                            <span>File or Check Leave</span>
                        </a>
                    @endif
                    <a class="quick-action mb-2" href="{{ route('dtr-read') }}">
                        <i class="fas fa-clock"></i>
                        <span>View DTR</span>
                    </a>
                    <a class="quick-action" href="{{ route('drive') }}">
                        <i class="fas fa-folder-open"></i>
                        <span>Open SPMS</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>
@else
@php
    $adminUser = auth()->guard($guard)->user();
    $adminProfilePath = public_path('Profile/Employee/' . $adminUser->profile);
    $adminProfile = $adminUser->profile && file_exists($adminProfilePath) ? asset('Profile/Employee/' . $adminUser->profile) : asset('Profile/Employee/default.png');
    $nowManila = now('Asia/Manila');
    $greeting = $nowManila->hour < 12 ? 'Good morning' : ($nowManila->hour < 18 ? 'Good afternoon' : 'Good evening');
    $greetingIcon = $nowManila->hour < 12 ? 'fas fa-sun' : ($nowManila->hour < 18 ? 'fas fa-cloud-sun' : 'fas fa-moon is-evening');
    $pendingTotal = $leaveappCount + $eliCount + $workexpCount + $learDevCount + $volWorkCount;
    $pendingItems = [
        ['route' => route('readPending', 1), 'label' => 'Leave Applications', 'count' => $leaveappCount, 'icon' => 'fas fa-file-alt', 'tone' => 'tone-green'],
        ['route' => route('readPending', 2), 'label' => 'Eligibility', 'count' => $eliCount, 'icon' => 'fas fa-award', 'tone' => 'tone-blue'],
        ['route' => route('readPending', 3), 'label' => 'Work Experience', 'count' => $workexpCount, 'icon' => 'fas fa-tools', 'tone' => 'tone-purple'],
        ['route' => route('readPending', 5), 'label' => 'Learning & Development', 'count' => $learDevCount, 'icon' => 'fas fa-book', 'tone' => 'tone-orange'],
        ['route' => route('readPending', 4), 'label' => 'Voluntary Works', 'count' => $volWorkCount, 'icon' => 'fas fa-hands-helping', 'tone' => 'tone-teal'],
    ];
    $firstPending = collect($pendingItems)->firstWhere('count', '>', 0);
    $pendingLink = $firstPending ? $firstPending['route'] : route('readPending', 1);
    $statusRows = [
        1 => ['label' => 'Regular', 'color' => 'var(--cpsu-green-600)'],
        2 => ['label' => 'Full-time / Part-time', 'color' => 'var(--cpsu-gold-500)'],
        3 => ['label' => 'Part-time / Part-time', 'color' => '#3fb37a'],
        4 => ['label' => 'Job Order', 'color' => '#7a8b82'],
    ];
    $todayMd = $nowManila->format('F j');
@endphp
<div class="container-fluid dash">
    <div class="dash-hero">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div class="d-flex align-items-center mb-3 mb-md-0">
                <img src="{{ $adminProfile }}" class="dash-avatar mr-3" alt="Profile Image">
                <div>
                    <div class="dash-eyebrow">{{ $nowManila->format('l, F j, Y') }}</div>
                    <h3>{{ $greeting }}, {{ ucwords(strtolower($adminUser->fname)) }} <i class="{{ $greetingIcon }} dash-greet-icon" aria-hidden="true"></i></h3>
                    <div class="dash-sub">{{ ucfirst($adminUser->role) }} &middot; CPSU Human Resource Information System</div>
                </div>
            </div>
            <a href="{{ $pendingLink }}" class="dash-chip">
                <span class="dash-chip-icon"><i class="fas fa-inbox"></i></span>
                {{ number_format($pendingTotal) }} pending {{ $pendingTotal == 1 ? 'item' : 'items' }} to review
                <i class="fas fa-chevron-right fa-xs"></i>
            </a>
        </div>
    </div>

    <section class="content">
        <div class="row">
            <div class="col-lg-8 col-sm-12">
                <div class="row">
                    <div class="col-md-4 col-12">
                        <div class="stat-card tone-green">
                            <span class="stat-icon"><i class="fa-solid fa-user-tie"></i></span>
                            <i class="fa-solid fa-users stat-watermark" aria-hidden="true"></i>
                            <div>
                                <div class="stat-label">Employees</div>
                                <div class="stat-value">{{ number_format($totalEmployees) }}</div>
                                <div class="stat-foot">Active workforce</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="stat-card tone-gold">
                            <span class="stat-icon"><i class="fa-solid fa-users-viewfinder"></i></span>
                            <i class="fa-solid fa-people-group stat-watermark" aria-hidden="true"></i>
                            <div>
                                <div class="stat-label">Present</div>
                                <div class="stat-value">{{ number_format($dtrCount) }}</div>
                                <div class="stat-foot">With DTR today</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="stat-card tone-rose">
                            <span class="stat-icon"><i class="fas fa-user-clock"></i></span>
                            <i class="fa-solid fa-user-xmark stat-watermark" aria-hidden="true"></i>
                            <div>
                                <div class="stat-label">Absent</div>
                                <div class="stat-value">{{ number_format($totalEmployees - $dtrCount) }}</div>
                                <div class="stat-foot">No DTR today</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dash-card">
                    <div class="dash-card-header">
                        <h5><i class="fas fa-clipboard-check" style="color: var(--cpsu-green-600);"></i>Pending Reviews</h5>
                        <span class="dash-card-hint d-none d-sm-inline">Click a card to review submissions <i class="fas fa-arrow-right fa-xs ml-1" style="color: var(--cpsu-green-600);"></i></span>
                    </div>
                    <div class="dash-card-body">
                        <div class="review-grid">
                            @foreach($pendingItems as $item)
                                <a href="{{ $item['route'] }}" class="review-tile {{ $item['tone'] }}">
                                    <div class="review-top">
                                        <i class="{{ $item['icon'] }}"></i>
                                        <span class="review-count {{ $item['count'] == 0 ? 'is-zero' : '' }}">{{ number_format($item['count']) }}</span>
                                    </div>
                                    <div class="review-label">{{ $item['label'] }}</div>
                                    <div class="review-cta">Review <i class="fas fa-arrow-right fa-xs ml-1"></i></div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="dash-card dash-calendar">
                    <div class="dash-card-header">
                        <h5><i class="fas fa-calendar-alt" style="color: var(--cpsu-green-600);"></i>Campus Events</h5>
                    </div>
                    <div class="dash-card-body">
                        <div id="external-events"></div>
                        <div id="calendar" class="bg-white"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-sm-12">
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h5><i class="fas fa-chart-column" style="color: var(--cpsu-green-600);"></i>Employee Status</h5>
                        <span class="dash-card-hint">{{ number_format($totalEmployees) }} total</span>
                    </div>
                    <div class="dash-card-body">
                        @foreach($statusRows as $statusKey => $row)
                            @php $stat = $empStatusPercentages->get($statusKey); @endphp
                            <div class="status-row">
                                <div class="status-meta">
                                    <strong>{{ $row['label'] }}</strong>
                                    <span>{{ number_format($stat['count']) }} &middot; {{ number_format($stat['percentage'], 2) }}%</span>
                                </div>
                                <div class="status-bar">
                                    <div style="width: {{ number_format($stat['percentage'], 2) }}%; background: {{ $row['color'] }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="dash-card">
                    <div class="dash-card-header">
                        <h5><i class="fas fa-gift" style="color: var(--cpsu-gold-500);"></i>Upcoming Birthdays</h5>
                    </div>
                    <ul class="bday-list">
                        @forelse($upcomingBirthdays as $employee)
                            @php
                                $imageUrl = asset('Profile/Employee/' . $employee->profile);
                                $imagePath = public_path('Profile/Employee/' . $employee->profile);
                                $isToday = $employee->bdate->format('F j') == $todayMd;
                            @endphp
                            <li class="{{ $isToday ? 'is-today' : '' }}">
                                <img src="{{ file_exists($imagePath) ? $imageUrl : asset('Profile/Employee/default.png') }}" alt="Profile Image">
                                <div style="min-width: 0;">
                                    <div class="bday-name">{{ ucfirst(strtolower($employee->lname)) . ' ' . ucfirst(strtolower($employee->fname)) }}</div>
                                    <div class="bday-office">{{ $employee->office_abbr }}</div>
                                </div>
                                <div class="bday-date">
                                    @if($isToday)
                                        <span class="badge">Today <i class="fas fa-birthday-cake"></i></span>
                                    @else
                                        {{ $employee->bdate->format('F j') }}
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="text-muted">No upcoming birthdays.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </section>
</div>
@endif
<script>
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };
</script>
@if($guard == 'employee')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.jQuery || !jQuery.fn.daterangepicker) {
            return;
        }

        const rangeInput = $('#dashboard_date_range');
        const dateFrom = $('#date_from');
        const dateTo = $('#date_to');

        // Payroll cutoffs: 1st-15th and 16th-end of month.
        const cutoffFor = function (date) {
            return date.date() <= 15
                ? [date.clone().startOf('month'), date.clone().date(15)]
                : [date.clone().date(16), date.clone().endOf('month')];
        };
        const thisCutoff = cutoffFor(moment());
        const previousCutoff = cutoffFor(thisCutoff[0].clone().subtract(1, 'day'));

        rangeInput.daterangepicker({
            startDate: moment(dateFrom.val(), 'YYYY-MM-DD'),
            endDate: moment(dateTo.val(), 'YYYY-MM-DD'),
            autoUpdateInput: true,
            locale: {
                format: 'MMM D, YYYY',
                separator: ' - '
            },
            ranges: {
                'This Cutoff': thisCutoff,
                'Previous Cutoff': previousCutoff,
                'This Week': [moment().startOf('week'), moment().endOf('week')],
                'Today': [moment(), moment()],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, function (start, end) {
            dateFrom.val(start.format('YYYY-MM-DD'));
            dateTo.val(end.format('YYYY-MM-DD'));
            rangeInput.closest('form').trigger('submit');
        });
    });
</script>
@endif
{{-- <script>
  // Get the current date
  const currentDate = new Date();
  
  // Get the current month and year
  const currentMonth = currentDate.getMonth() + 1; // Months are zero-indexed
  const currentYear = currentDate.getFullYear();
  
  // Format the month to always have two digits
  const formattedMonth = currentMonth < 10 ? '0' + currentMonth : currentMonth;
  
  // Set the value of the input to the current month and year
  document.getElementById('monthInput').value = `${currentYear}-${formattedMonth}`;
  
  // Disable the year selection
  document.getElementById('monthInput').addEventListener('click', function() {
      this.showPicker = () => {};
  });
</script> --}}
@endsection
