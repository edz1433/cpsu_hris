@extends('layouts.master')

@section('body')
<link rel="stylesheet" href="{{ asset('css/subfolder.css') }}">
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2">
            @include('drive.submenu')
        </div>
        @include('drive.modals')
        <div class="col-lg-10">
            <div class="card card-info card-outline">   
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-success1"><i class="fas fa-dashboard"></i> Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('drive') }}" class="text-success1">Drive</a></li>
                    
                    @foreach($connFolders as $connFolder)
                        <li class="breadcrumb-item"><a href="{{ route('sub-folder', $connFolder->id) }}" class="text-success1">{{ $connFolder->folder_name }}</a></li>
                    @endforeach
                    <li class="breadcrumb-item active text-muted" aria-current="page">{{ $folder->folder_name }}</li>
                </ol>
            </nav>
                @php $connfold = $folder->connected_folder; @endphp 
                @if($subfolder->isNotEmpty())
                    <div class="card-body folder-grid">
                        @php
                            $useroffid = ($guard == 'employee') ? (empty($office)) ? auth()->guard('employee')->user()->emp_dept : $office->id : null;
                        @endphp
                    
                        @forelse ($subfolder as $folder)
                            @php 
                                $officearray = explode(',', $folder->office_access); 
                                $checkaccess = $guard == 'employee' && !in_array($useroffid, $officearray) && $folder->office_access !== "All";
                            @endphp
                    
                            @if(!$finalcond)
                                <div class="@if($finalcond) folder-items @else folder-item @endif;" id="folder-{{ $folder->id }}">
                                    <a href="{{ route('sub-folder', $folder->id) }}">
                                        <i class="folder-icon fas"></i>
                                        @if($finalcond)
                                            <i class="fas fa-lock fa-1x" style="margin-left: -25px; color: rgb(255, 255, 255); box-shadow: 4px 4px 8px rgba(0, 0, 0, 0.2);"></i>
                                        @endif
                                        <span class="folder-name">{{ $folder->folder_name }}</span>
                                    </a>
                                    @if($guard !== 'employee')
                                        <div class="folder-options">
                                            <div class="dropdown">
                                                <i class="fas fa-ellipsis-v" id="folderOptionsDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></i>
                                                <div class="dropdown-menu" aria-labelledby="folderOptionsDropdown">
                                                    <a class="dropdown-item" data-toggle="modal" data-target="#editFolderModal" onclick="editFolder({{ $folder->id }}, '{{$folder->folder_name}}')">Edit</a>
                                                    <button class="dropdown-item" onclick="confirmDelete({{ $folder->id }})">Delete</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @empty
                            <div class="no-folders">No folders found..</div>
                        @endforelse
                    </div>
                    <hr>  
                @endif 
                <div class="card-header">
                    <h3 class="card-title"></h3>
                    <div class="card-tools">
                        <div class="input-group input-group-sm" style="width: 150px;">
                            <input type="text" name="table_search" autocomplete="off" class="form-control float-right" placeholder="Search">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>    
                    </div>
                </div>
                <div class="card-body table-responsive p-0" style="height: 400px;">
                    <table class="table table-head-fixed text-nowrap">
                        <tbody>
                            @foreach ($opcrs as $key => $mfoItems)
                                @php
                                    $employee = $mfoItems->first();
                                    $fullName = Str::upper("{$employee->fname} {$employee->mname} {$employee->lname}");
                                    $year = $employee->year;
                                @endphp
                                <tr onclick="showForm('{{ shortEncrypt($employee->empid) }}', '{{ shortEncrypt($employee->pr_number) }}')">
                                    @php
                                        $profileFile = $employee->profile ?? '';
                                        $sex = $employee->sex ?? 'Male';
                                        $profilePath = public_path('Profile/Employee/' . $profileFile);

                                        if (!empty($profileFile) && file_exists($profilePath)) {
                                            $image = asset('Profile/Employee/' . $profileFile);
                                        } else {
                                            $defaultImage = $sex === 'Female' ? 'default-female.png' : 'default.png';
                                            $image = asset('Profile/Employee/' . $defaultImage);
                                        }
                                    @endphp

                                    <td width="40">
                                        <img src="{{ $image }}" alt="User Image" class="profile-image">
                                    </td>

                                    <td><b>{{ $fullName }}</b></td>
                                    <td><b>{{ $foldercat.' '.strtoupper($year) }}</b> </td>
                                    @foreach ($mfoItems as $item)
                                        <td>
                                            <b>{{ $item->mfo }} (<span class="text-danger">{{ $item->percent }}%</span>)</b>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>                        
                    </table>
                </div>
            </div>  
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="table_search"]');
        const tableRows = document.querySelectorAll('.table tbody tr');
    
        searchInput.addEventListener('input', function() {
            const searchTerm = searchInput.value.toLowerCase();
    
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const found = Array.from(cells).some(cell => cell.textContent.toLowerCase().includes(searchTerm));
                row.style.display = found ? '' : 'none';
            });
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
