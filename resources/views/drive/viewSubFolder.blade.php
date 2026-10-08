@extends('layouts.master')

@section('body')
@php
    $isManager = $guard == 'web' || in_array($userid, $pmtsmember ?? []);
    // [label, pill tone, needs a reason] — Returned asks for a reason (masterScript's .btn-status-with-comment).
    $statusLabels = [
        0 => ['Ongoing', 'is-start', false],
        1 => ['Assigned', 'is-info', false],
        2 => ['Reviewing', 'is-info', false],
        3 => ['Done', 'is-added', false],
        4 => ['Returned', 'is-deducted', true],
        5 => $isManager ? ['Request review', 'is-info', false] : ['Waiting...', 'is-start', false],
    ];
    $folderFull = [
        1 => 'Office Performance Commitment and Review',
        2 => 'Division Performance Commitment and Review',
        3 => 'Individual Performance Commitment and Review',
    ];
    $showStatus = $id != 1;
    $useroffid = ($guard == 'employee') ? (empty($office) ? auth()->guard('employee')->user()->emp_dept : $office->id) : null;
    $currentFolder = $folder;
@endphp
<div class="container-fluid dash">
    <div class="dtr-head">
        <div>
            <nav class="spms-crumbs" aria-label="breadcrumb">
                <a href="{{ route('drive') }}"><i class="fas fa-hdd"></i> My Drive</a>
                @foreach($connFolders as $connFolder)
                    <i class="fas fa-chevron-right"></i>
                    <a href="{{ route('sub-folder', shortEncrypt($connFolder->id)) }}">{{ $connFolder->folder_name }}</a>
                @endforeach
                <i class="fas fa-chevron-right"></i>
                <span aria-current="page">{{ $currentFolder->folder_name }}</span>
            </nav>
            <h1>{{ $currentFolder->folder_name }}</h1>
            <p>{{ $folderFull[$id] ?? 'Performance records in this folder.' }}</p>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-2 col-lg-3">
            @include('drive.submenu')
        </div>
        @include('drive.modals')
        <div class="col-xl-10 col-lg-9">
            @if($subfolder->isNotEmpty())
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5><i class="fas fa-folder" style="color: #e5b222;"></i>Folders</h5>
                    <span class="dash-card-hint">{{ count($subfolder) }}</span>
                </div>
                <div class="dash-card-body">
                    <div class="spms-grid">
                        @foreach ($subfolder as $sub)
                            @php
                                $officearray = explode(',', $sub->office_access);
                                // Employees only see folders shared with their office (or with everyone).
                                $noAccess = $guard == 'employee' && !in_array($useroffid, $officearray) && $sub->office_access !== "All";
                            @endphp
                            @continue($noAccess)
                            <div class="spms-folder" id="folder-{{ $sub->id }}">
                                <a href="{{ route('sub-folder', shortEncrypt($sub->id)) }}" class="spms-folder-link">
                                    <span class="spms-folder-icon" aria-hidden="true"><i class="fas fa-folder"></i></span>
                                    <span class="spms-folder-text">
                                        <span class="spms-folder-name">{{ $sub->folder_name }}</span>
                                        <span class="spms-folder-desc">Folder</span>
                                    </span>
                                </a>
                                @if($guard !== 'employee')
                                    <div class="spms-folder-options dropdown">
                                        <button type="button" class="lv-icon-btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Folder options" aria-label="Folder options">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#editFolderModal" onclick="editFolder({{ $sub->id }}, '{{ $sub->folder_name }}')">
                                                <i class="fas fa-pen mr-2"></i> Rename
                                            </a>
                                            <button type="button" class="dropdown-item text-danger" onclick="confirmDelete({{ $sub->id }})">
                                                <i class="fas fa-trash mr-2"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <div class="dash-card">
                <div class="dash-card-header flex-wrap">
                    <h5><i class="fas fa-file-signature" style="color: var(--cpsu-green-600);"></i>Records <span class="eli-count">{{ count($opcrs) }}</span></h5>
                    @if(count($opcrs) > 0)
                        <label class="emp-search mb-0" for="spmsSearch">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input type="search" name="table_search" id="spmsSearch" placeholder="Search name, year, MFO..." autocomplete="off" aria-label="Search records">
                        </label>
                    @endif
                </div>
                @if(count($opcrs) > 0)
                {{-- Only scrolls on small screens, so the status menu isn't clipped on desktop. --}}
                <div class="table-responsive-lg">
                    <table class="table lv-table spms-table" id="spmsTable">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Period</th>
                                <th>MFOs</th>
                                @if($showStatus)<th class="text-center">Status</th>@endif
                                <th width="40"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($opcrs as $key => $mfoItems)
                                @php
                                    $employee = $mfoItems->first();
                                    $fullName = Str::upper(trim("{$employee->fname} {$employee->mname} {$employee->lname}"));
                                    $year = $employee->year;
                                    $status = $employee->status ?? 0;
                                    $profileFile = $employee->profile ?? '';
                                    $sex = $employee->sex ?? 'Male';
                                    $profilePath = public_path('Profile/Employee/' . $profileFile);
                                    if (!empty($profileFile) && file_exists($profilePath)) {
                                        $image = asset('Profile/Employee/' . $profileFile);
                                    } else {
                                        $image = asset('Profile/Employee/' . ($sex === 'Female' ? 'default-female.png' : 'default.png'));
                                    }
                                    [$statusText, $statusTone] = $statusLabels[$status] ?? $statusLabels[0];
                                    // Same rule as before: managers always; employees once it is past "Ongoing".
                                    $canOpen = ($guard != 'web' && $employee->status != 0) || $guard == 'web' || in_array($userid, $pmtsmember);
                                @endphp
                                <tr class="{{ $canOpen ? 'is-clickable' : '' }}"
                                    @if($canOpen) onclick="showForm('{{ shortEncrypt($employee->empid) }}', '{{ shortEncrypt($employee->pr_number) }}')" title="Open the rating form" @endif>
                                    <td>
                                        <div class="emp-person" style="min-width: 200px;">
                                            <img src="{{ $image }}" alt="" class="spms-avatar">
                                            <span class="um-name">{{ $fullName }}</span>
                                        </div>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <span class="spms-period">{{ $foldercat }}</span>
                                        <b>{{ strtoupper($year) }}</b>
                                    </td>
                                    <td>
                                        <div class="spms-mfos">
                                            @foreach ($mfoItems as $item)
                                                <span class="spms-mfo">{{ $item->mfo }} <b>{{ $item->percent }}%</b></span>
                                            @endforeach
                                        </div>
                                    </td>
                                    @if($showStatus)
                                    <td class="text-center" onclick="event.stopPropagation();" style="white-space: nowrap;">
                                        @if($isManager)
                                            <div class="dropdown d-inline-block">
                                                <button type="button" class="spms-status lv-pill {{ $statusTone }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Change status">
                                                    {{ $statusText }} <i class="fas fa-chevron-down"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right spms-status-menu">
                                                    @foreach($statusLabels as $val => [$label, $tone, $needsReason])
                                                        @continue($status == $val || $val == 5)
                                                        @if($needsReason)
                                                            <button type="button" class="dropdown-item btn-status-with-comment"
                                                                    data-prnumber="{{ $employee->pr_number }}"
                                                                    data-stat="{{ $val }}"
                                                                    data-label="{{ $label }}">
                                                                <span class="lv-pill {{ $tone }}">{{ $label }}</span>
                                                                <small>asks for a reason</small>
                                                            </button>
                                                        @else
                                                            <form action="{{ route('updateStat') }}" method="POST" class="m-0">
                                                                @csrf
                                                                <input type="hidden" name="prnumber" value="{{ $employee->pr_number }}">
                                                                <input type="hidden" name="stat" value="{{ $val }}">
                                                                <button type="submit" class="dropdown-item">
                                                                    <span class="lv-pill {{ $tone }}">{{ $label }}</span>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <span class="lv-pill {{ $statusTone }}">{{ $statusText }}</span>
                                        @endif
                                    </td>
                                    @endif
                                    <td class="text-center lv-muted">
                                        @if($canOpen)<i class="fas fa-external-link-alt" aria-hidden="true"></i>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="dtr-empty py-4 d-none" id="spmsNoMatch">
                    <h6>No matches</h6>
                    <p>Nothing here matches that search.</p>
                </div>
                @else
                <div class="dtr-empty">
                    <div class="dtr-empty-icon"><i class="fas fa-file-signature"></i></div>
                    <h6>No records yet</h6>
                    <p>{{ $currentFolder->folder_name }} records added for this folder show up here.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="table_search"]');
        if (!searchInput) return;
        const tableRows = document.querySelectorAll('#spmsTable tbody tr');
        const noMatch = document.getElementById('spmsNoMatch');

        searchInput.addEventListener('input', function() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let shown = 0;

            tableRows.forEach(row => {
                const found = row.textContent.toLowerCase().includes(searchTerm);
                row.style.display = found ? '' : 'none';
                if (found) shown++;
            });
            noMatch.classList.toggle('d-none', shown > 0);
        });
    });
</script>
<script>
    function showForm(empid, prnumber) {
        let route = "";
        let folder = parseInt("{{ $id }}"); // Convert Blade string to integer

        switch (folder) {
            case 1:
                route = "{{ route('perRatingOpcr', ['cat' => ':cat', 'empid' => ':empid', 'prnumber' => ':prnumber']) }}";
                break;
            case 2:
                route = "{{ route('perRatingDpcr', ['cat' => ':cat', 'empid' => ':empid', 'prnumber' => ':prnumber']) }}";
                break;
            case 3:
                route = "{{ route('perRatingIpcr', ['cat' => ':cat', 'empid' => ':empid', 'prnumber' => ':prnumber']) }}";
                break;
            default:
                alert("Invalid category: " + folder);
                return;
        }

        const url = route
            .replace(':cat', 1)
            .replace(':empid', empid)
            .replace(':prnumber', prnumber);

        window.open(url, "_blank");
    }
</script>
@endsection
