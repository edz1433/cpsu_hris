@extends('layouts.master')

@section('body')
<link rel="stylesheet" href="{{ asset('css/folder.css') }}">
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
                        @if($guard == "web")
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-success1"><i class="fas fa-dashboard"></i> Dashboard</a></li>
                        @endif
                        <li class="breadcrumb-item"><a href="{{ route('drive') }}" class="text-success1">Drive</a></li>
                        <li class="breadcrumb-item text-muted">My Drive</li>
                    </ol> 
                </nav>
                <div class="card-body folder-grid">
                    @php
                        $useroffid = ($guard == 'employee') ? (empty($office)) ? auth()->guard('employee')->user()->emp_dept : $office->id  : null;
                    @endphp
                
                    @forelse ($docFolder as $folder) 
                        @php 
                            $officearray = explode(',', $folder->office_access); 
                            $checkaccess = !in_array($useroffid, $officearray);
                            
                            $finalcond = $guard == 'employee' && $checkaccess && $folder->office_access != "All";
                        @endphp
            
                        <div class="@if($finalcond) folder-items @else folder-item @endif;" id="folder-{{ $folder->id }}">
                            <a href="{{ route('sub-folder', $folder->id) }}" style="pointer-events: @if($finalcond) none @endif;">
                                <i class="folder-icon fas"></i>
                                @if($finalcond)
                                    <i class="fas fa-lock fa-2x" style="margin-left: -25px; color: rgb(255, 255, 255); box-shadow: 4px 4px 8px rgba(0, 0, 0, 0.2);"></i>
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
                    @empty
                        <div class="no-folders">No folders found..</div>
                    @endforelse
                </div>                
            </div>
        </div>
    </div>
</div>
@endsection
