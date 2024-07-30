<div class="btn-group w-100">
    <button type="button" class="btn bg-success1 btn-block mb-3 dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-plus"></i> New
    </button>
    <div class="dropdown-menu w-100">
        @if($guard == 'web' && (request()->is('spms') || request()->is('spms/*'))) 
        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#createFolderModal">Create Folder</a>
        @endif
        @if($guard == 'employee' && request()->is('spms/*')) 
            @if($folder->folder_category == 'subfolder')
            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#uploadFileModal">Create PR</a>
            @endif
        @endif       
    </div>
</div> 

<div class="card card-info card-outline p-1">
    <h5 class="card-title" style="font-size: 17pt"></h5>
    <ul class="nav nav-pills nav-sidebar nav-compact flex-column">
        <li class="nav-item mb-1">
            <a href="{{ route('drive') }}" class="nav-link2 {{ request()->is('spms') || request()->is('spms/*') ? 'active1' : '' }}" id="allButton">
                <i class="fas fa-hdd text-success1"></i>
                <span class="ml-2 text-success1">My Drive</span>
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="" class="nav-link2" id="ppeButton">
                <i class="fas fa-file-alt text-muted"></i>
                <span class="ml-2 text-muted">Logs</span>
            </a>
        </li>
        <li class="nav-item mb-1">
            <a href="" class="nav-link2" id="trashButton">
                <i class="fas fa-trash text-muted"></i>
                <span class="ml-2 text-muted">Trash</span>
            </a>
        </li>
    </ul>                     
</div>

<div class="card p-1">
    <h5 class="card-title" style="font-size: 17pt"></h5>
    <ul class="nav nav-pills nav-sidebar nav-compact flex-column">
        <li class="nav-item">
            <a href="{{ route('drive-account') }}" class="nav-link2 {{ request()->is('spms') ? 'active' : '' }}" id="allButton">
                <i class="fas fa-users text-muted"></i>
                <span class="ml-2 text-muted">Accounts</span>
            </a>
        </li>
    </ul>                     
</div>