
@php
    $isPersonnelActive = request()->is('spms-set/personnel*') || request()->is('spms-set/personnel*');
    $isSpmsActive = request()->is('spms') || request()->is('spms/*');
    $isPmtActive = request()->is('spms-set/pmt*');
    $isPmtLogActive = request()->is('spms-logs');
    $isMfoActive = request()->is('spms-mfo-settings*') || request()->is('spms-mfo-settings/edit*');
@endphp

@if($guard == "web" || in_array($userid, $pmtsmember ?? []))
<div class="btn-group w-100">
    <button type="button" class="btn bg-success1 btn-block mb-3 dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-plus"></i> New
    </button>
    @if(!request()->is('spms-pmt'))
        <div class="dropdown-menu w-100">
            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#createFolderModal">Create Folder</a>
            @if(request()->is('spms/*')) 
                @if($folder->folder_category !== 'subfolder')
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#createOpcrModal">Create OPCR</a>
                @endif
            @endif       
        </div>
    @endif
</div> 
@endif
<div class="card card-info card-outline p-1">
    <h5 class="card-title" style="font-size: 17pt"></h5>
    <ul class="nav nav-pills nav-sidebar nav-compact flex-column">
        <li class="nav-item mb-1">
            <a href="{{ route('drive') }}" class="nav-link2 {{ $isSpmsActive ? 'active1' : '' }}" id="allButton">
                <i class="fas fa-hdd {{ $isSpmsActive ? 'text-success1' : 'text-muted' }}"></i>
                <span class="ml-2 {{ $isSpmsActive ? 'text-success1' : 'text-muted' }}">My Drive</span>
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="" class="nav-link2" id="ppeButton">
                <i class="fas fa-file-alt text-muted"></i>
                <span class="ml-2 text-muted">Logs</span>
            </a>
        </li>
    </ul>                     
</div>

@if($role == 'Administrator' || $role == 'HR Administrator' || in_array($userid, $pmtsmember ?? []))
    <div class="card p-1">
        <h5 class="card-title" style="font-size: 17pt"></h5>
        <ul class="nav nav-pills nav-sidebar nav-compact flex-column">
            <li class="nav-item mb-1">
                <a href="{{ route('spmsPersonnlist', 'pmt') }}" class="nav-link2 {{ $isPmtActive ? 'active1' : 'text-muted' }}" id="trashButton">
                    <i class="fas fa-user {{ $isPmtActive ? 'text-success1' : 'text-muted' }}"></i>
                    <span class="ml-2 {{ $isPmtActive ? 'text-success1' : 'text-muted' }}">PMT</span>
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="{{ route('spmsPersonnlist', 'personnel') }}" class="nav-link2 {{ $isPersonnelActive ? 'active1' : 'text-muted' }}" id="trashButton">
                    <i class="fas fa-user {{ $isPersonnelActive ? 'text-success1' : 'text-muted' }}"></i>
                    <span class="ml-2 {{ $isPersonnelActive ? 'text-success1' : 'text-muted' }}">Personnel</span>
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="{{ route('mfoSettings') }}" class="nav-link2 {{ $isMfoActive ? 'active1' : 'text-muted' }}" id="trashButton">
                    <i class="fas fa-cogs {{ $isMfoActive ? 'text-success1' : 'text-muted' }}"></i>
                    <span class="ml-2 {{ $isMfoActive ? 'text-success1' : 'text-muted' }}">MFO Settings</span>
                </a>
            </li>
        </ul>                    
    </div>
@endif
