@extends('layouts.master')

@section('body')
@php
    // Shown under the default folders; folders admins add later just say "Folder".
    $folderInfo = [
        'OPCR' => ['Office Performance Commitment and Review', 'fa-building'],
        'DPCR' => ['Division Performance Commitment and Review', 'fa-sitemap'],
        'IPCR' => ['Individual Performance Commitment and Review', 'fa-user-check'],
    ];
    $useroffid = ($guard == 'employee')
        ? (empty($office)
            ? auth()->guard('employee')->user()->emp_dept
            : $office->id)
        : null;
@endphp
<div class="container-fluid dash">
    <div class="dtr-head">
        <div>
            <h1>SPMS</h1>
            <p>Strategic Performance Management System &middot; performance commitments and ratings, by folder.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-2 col-lg-3">
            @include('drive.submenu')
        </div>
        @include('drive.modals')
        <div class="col-xl-10 col-lg-9">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5><i class="fas fa-hdd" style="color: var(--cpsu-green-600);"></i>My Drive</h5>
                    <span class="dash-card-hint">{{ count($docFolder) }} {{ count($docFolder) == 1 ? 'folder' : 'folders' }}</span>
                </div>
                <div class="dash-card-body">
                    <div class="spms-grid">
                    @forelse ($docFolder as $folder)
                        @php
                            $officearray = explode(',', $folder->office_access);
                            $checkaccess = !in_array($useroffid, $officearray);
                            $isUserInPmts = in_array($userid, $pmtsmember ?? []);

                            // Base office-access check
                            $finalcond = $guard == 'employee'
                                && $checkaccess
                                && $folder->office_access != "All";

                            // CATEGORY & PMTS ACCESS RULES
                            $categoryAllowed = false;

                            if ($guard == 'employee' && !$isUserInPmts) {
                                // Category 1–5 → folders 2 & 3
                                if (in_array($category, [1,2,3,4,5]) && in_array($loop->iteration, [2,3])) {
                                    $categoryAllowed = true;
                                }

                                // Category 6–7 → folder 3 only
                                if (in_array($category, [6,7]) && $loop->iteration == 3) {
                                    $categoryAllowed = true;
                                }
                            }

                            // PMTS override → folders 1,2,3 always open
                            if ($isUserInPmts && in_array($loop->iteration, [1,2,3])) {
                                $categoryAllowed = true;
                            }

                            // FINAL LOCK: lock if employee and not allowed
                            $isLocked = ($guard == 'employee') && !$categoryAllowed;

                            [$folderDesc, $folderIcon] = $folderInfo[strtoupper(trim($folder->folder_name))] ?? ['Folder', null];
                        @endphp

                        <div class="spms-folder {{ $isLocked ? 'is-locked' : '' }}" id="folder-{{ $folder->id }}">
                            <a href="{{ route('sub-folder', shortEncrypt($folder->id)) }}" class="spms-folder-link"
                               @if($isLocked) style="pointer-events: none;" aria-disabled="true" tabindex="-1" @endif>
                                <span class="spms-folder-icon" aria-hidden="true">
                                    <i class="fas fa-folder"></i>
                                    @if($folderIcon)<i class="fas {{ $folderIcon }} spms-folder-badge"></i>@endif
                                </span>
                                <span class="spms-folder-text">
                                    <span class="spms-folder-name">{{ $folder->folder_name }}</span>
                                    <span class="spms-folder-desc">{{ $folderDesc }}</span>
                                </span>
                                @if($isLocked)
                                    <span class="spms-folder-state"><i class="fas fa-lock"></i> No access</span>
                                @endif
                            </a>

                            @if($guard !== 'employee' && $folder->default_folder == 0)
                                <div class="spms-folder-options dropdown">
                                    <button type="button" class="lv-icon-btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Folder options" aria-label="Folder options">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="#"
                                           data-toggle="modal"
                                           data-target="#editFolderModal"
                                           onclick="editFolder({{ $folder->id }}, '{{ $folder->folder_name }}')">
                                            <i class="fas fa-pen mr-2"></i> Rename
                                        </a>
                                        <button type="button" class="dropdown-item text-danger" onclick="confirmDelete({{ $folder->id }})">
                                            <i class="fas fa-trash mr-2"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="dtr-empty py-4" style="grid-column: 1 / -1;">
                            <div class="dtr-empty-icon"><i class="far fa-folder-open"></i></div>
                            <h6>No folders yet</h6>
                            <p>Folders created with <b>New</b> show up here.</p>
                        </div>
                    @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
