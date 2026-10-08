@php
    $isPersonnelActive = request()->is('spms-set/personnel*');
    $isSpmsActive = request()->is('spms') || request()->is('spms/*');
    $isPmtActive = request()->is('spms-set/pmt*');
    $isPmtLogActive = request()->is('spms-logs');
    $isMfoActive = request()->is('spms-mfo-settings*') || request()->is('spms-mfo-settings/edit*');
    $isPmtMember = in_array($userid, $pmtsmember ?? []);
@endphp

@if($guard == "web" || $isPmtMember)
<div class="dropdown mb-3 spms-new">
    <button type="button" class="lv-btn is-primary w-100 justify-content-center" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" @if(request()->is('spms-pmt')) disabled @endif>
        <i class="fas fa-plus"></i> New
    </button>
    @if(!request()->is('spms-pmt'))
        <div class="dropdown-menu w-100 spms-new-menu">
            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#createFolderModal"><i class="fas fa-folder-plus"></i> Create folder</a>
            @if(request()->is('spms/*') && $id == 1)
                @if($folder->folder_category !== 'subfolder')
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#createOpcrModal"><i class="fas fa-file-signature"></i> Create OPCR</a>
                @endif
            @endif
        </div>
    @endif
</div>
@endif

<div class="dash-card">
    <nav class="pds-nav py-2" aria-label="SPMS">
        <a href="{{ route('drive') }}" class="{{ $isSpmsActive ? 'is-active' : '' }}" @if($isSpmsActive) aria-current="page" @endif>
            <i class="fas fa-hdd pds-nav-icon"></i>
            <span class="pds-nav-label">My Drive</span>
        </a>
        <a href="" class="{{ $isPmtLogActive ? 'is-active' : '' }}">
            <i class="fas fa-history pds-nav-icon"></i>
            <span class="pds-nav-label">Logs</span>
        </a>
    </nav>

    @if($role == 'Administrator' || $role == 'HR Administrator' || $isPmtMember)
        <div class="pds-nav-group">Settings</div>
        <nav class="pds-nav pb-2" aria-label="SPMS settings">
            <a href="{{ route('spmsPersonnlist', 'pmt') }}" class="{{ $isPmtActive ? 'is-active' : '' }}" @if($isPmtActive) aria-current="page" @endif>
                <i class="fas fa-users-cog pds-nav-icon"></i>
                <span class="pds-nav-label">PMT</span>
            </a>
            <a href="{{ route('spmsPersonnlist', 'personnel') }}" class="{{ $isPersonnelActive ? 'is-active' : '' }}" @if($isPersonnelActive) aria-current="page" @endif>
                <i class="fas fa-user-friends pds-nav-icon"></i>
                <span class="pds-nav-label">Personnel</span>
            </a>
            <a href="{{ route('mfoSettings') }}" class="{{ $isMfoActive ? 'is-active' : '' }}" @if($isMfoActive) aria-current="page" @endif>
                <i class="fas fa-sliders-h pds-nav-icon"></i>
                <span class="pds-nav-label">MFO Settings</span>
            </a>
        </nav>
    @endif
</div>
