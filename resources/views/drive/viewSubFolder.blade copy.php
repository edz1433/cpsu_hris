@extends('layouts.master')

@section('body')
<link rel="stylesheet" href="{{ asset('css/subfolder.css') }}">
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2">
            @include('Drive.submenu')
        </div>
        @include('Drive.modals')
        <div class="col-lg-10">
            <div class="card card-info card-outline">   
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    @if($guard == "web")
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-success1"><i class="fas fa-dashboard"></i> Dashboard</a></li>
                    @endif
                    <li class="breadcrumb-item"><a href="{{ route('drive') }}" class="text-success1">My Drive</a></li>
                    
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
                            $useroffid = ($guard == 'employee') ? (empty($office)) ? auth()->guard('employee')->user()->emp_dept : $office->id  : null;
                        @endphp
                
                        @forelse ($subfolder as $folder) 
                            @php 
                                $officearray = explode(',', $folder->office_access); 
                                $checkaccess = !in_array($useroffid, $officearray);
        
                                $finalcond = $guard == 'employee' && $checkaccess && $folder->office_access != "All";
                            @endphp
                
                            <div class="@if($finalcond) folder-items @else folder-item @endif;" id="folder-{{ $folder->id }}">
                                
                                <a href="{{ route('sub-folder', $folder->id) }}" style="pointer-events: @if($finalcond) none @endif;">
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
                        @empty
                            <div class="no-folders">No folders found..</div>
                        @endforelse
                    </div> 
                    <hr>
                    @endif
                        @php
                            function customGetFileIcon($filesext)
                            {
                                $icons = [
                                    'pdf' => 'fa-file-pdf text-danger',
                                    'doc' => 'fa-file-word text-primary',
                                    'docx' => 'fa-file-word text-primary',
                                    'xls' => 'fa-file-excel text-success',
                                    'xlsx' => 'fa-file-excel text-success',
                                    'ppt' => 'fa-file-powerpoint text-warning',
                                    'pptx' => 'fa-file-powerpoint text-warning',
                                    'zip' => 'fa-file-archive text-secondary',
                                    'rar' => 'fa-file-archive text-secondary',
                                    'png' => 'fa-file-image text-info',
                                    'jpg' => 'fa-file-image text-info',
                                    'jpeg' => 'fa-file-image text-info',
                                    'gif' => 'fa-file-image text-info',
                                    'txt' => 'fa-file-alt text-dark',
                                    'default' => 'fa-file text-muted',
                                ];
                            
                                $iconClass = $icons[$filesext] ?? $icons['default'];
                            
                                return '<i class="fas ' . $iconClass . ' fa-2x"></i>'; 
                            }
                        @endphp
                        <div id="dropArea">
                            @if (count($documents) > 0)
                                <div class="card-header">
                                    <h3 class="card-title"></h3>
                                    <div class="card-tools">
                                        <div class="input-group input-group-sm" style="width: 150px;">
                                            <input type="text" name="table_search" class="form-control float-right" placeholder="Search">
                                            <div class="input-group-append">
                                                <button type="submit" class="btn btn-default">
                                                <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </div>    
                                    </div>
                                </div>
                            @endif
                            <div class="card-body table-responsive p-0" style="height: 400px;">
                                @if (count($documents))
                                <table class="table table-head-fixed text-nowrap">
                                    @if (count($documents) > 0)
                                        @foreach ($documents as $doc)
                                            <tr id="tr-file-{{ $doc->id }}">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="file-thumbnail text-center">
                                                            {!! customGetFileIcon($doc->file_ext) !!}
                                                        </div>
                                                        <p class="file-name ml-2 mb-0">{{ $doc->file }}</p>
                                                    </div>
                                                </td>
                                                <td><i class="fas fa-circle-user fa-1x"></i>@if($doc->user_id == $uid) me @else {{ ucfirst(strtolower($doc->fname)) }} {{ ucfirst(strtolower($doc->lname)) }}                                                @endif</td>
                                                <td>
                                                    @php 
                                                    $dateString = $doc->created_at;
                                                        $dateTime = new DateTime($dateString);
                                                        $formattedDate = $dateTime->format("F j, Y g:i A");
                                                        echo $formattedDate;
                                                    @endphp
                                                </td> 
                                                <td>
                                                    <div class="file-options">
                                                        <div class="dropdown">
                                                            <button class="btn"  type="button" id="fileOptions" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </button>
                                                            <div class="dropdown-menu" aria-labelledby="fileOptions">
                                                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#editFilerModal" onclick="editFile({{ $doc->id }}, '{{ pathinfo($doc->file, PATHINFO_FILENAME) }}')">
                                                                    <i class="fas fa-edit"></i> Rename
                                                                </a>
                                                                <a class="dropdown-item" href="{{ url($folder->folder_path.'/'.$doc->file) }}" target="_blank">
                                                                    <i class="fas fa-eye"></i> View
                                                                </a>
                                                                <a class="dropdown-item" href="#"  onclick="deleteFile({{ $doc->id }})">
                                                                    <i class="fas fa-trash-alt"></i> Delete
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="6" class="text-center">No files found in this folder.</td>
                                        </tr>
                                    @endif
                                </table>
                                @else
                                    @if(!empty($connfold))
                                    <div class="container text-center">
                                        <img src="{{ asset('Uploads/pdf logo.png') }}" width="600" alt="" srcset="">
                                    </div>
                                    @endif
                                @endif
                                @if(!empty($connfold))
                                    <input type="file" id="fileInput" accept=".pdf" style="display: none;">
                                @endif
                            </div>
                        </div>
                    </div>
            </div>  
        </div>
    </div>
</div>
@endsection
