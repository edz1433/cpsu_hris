
<style>
#modal-prform .modal-dialog {
    max-width: 90%;
    height: 90%;
    margin: 30px auto;
}

#modal-prform .modal-content {
    height: 100%;
    display: flex;
    flex-direction: column;
}

#modal-prform .modal-body {
    flex: 1;
    overflow-y: auto;
}

#table-form {
    width: 100%;
    font-size: 10px;
}

#table-form td, th{
    border: 1px solid rgb(92, 85, 85);
}

.b-none{
    border: none !important;
    width: 18px !important;
}

.btn-outline-secondary {
    border-radius: 50px;
    width: 30px !important;
    height: 30px !important;
}
.border-b-n{
    border-bottom: none;
}

</style>
@if(request()->is('spms') || request()->is('spms/*'))
<div class="modal fade" id="createFolderModal" tabindex="-1" role="dialog" aria-labelledby="createFolderModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createFolderModalLabel">Create Folder</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" @if(request()->is('spms'))  action="{{ route('create-folder') }}"  @else  action="{{ route('create-subfolder', $folder->id) }}" @endif>
                    @csrf
                    <div class="col-md-12">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-folder"></i>
                                </span>
                            </div>
                            <input type="text" class="form-control" id="folderName" name="folderName" placeholder="Folder Name" autocomplete="off" required>
                        </div>
                        <span class="badge badge-secondary mb-1 mt-2">give access</span>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-building"></i>
                                </span>
                            </div>
                            <select class="form-control select2" style="width: 90%;" name="office_access[]" multiple required>
                                <option value="All" selected>All</option>
                                @foreach ($offices as $q)
                                    <option value="{{ $q->id }}">{{ $q->office_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Create Folder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editFolderModal" tabindex="-1" role="dialog" aria-labelledby="createFolderModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createFolderModalLabel">Rename Folder</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('update-folder') }}">
                    @csrf
                    <div class="form-group">
                        <input type="hidden" name="fid" id="fid">
                        <input type="text" class="form-control" id="folder-naame" name="folderName" placeholder="Folder Name" autocomplete="off" required>
                    </div>
                    <span class="badge badge-secondary mb-1 mt-2">give access</span>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-building"></i>
                            </span>
                        </div>
                        <select class="form-control select2" style="width: 90%;" name="office_access[]" multiple required>
                            <option value="All" selected>All</option>
                            @foreach ($offices as $q)
                                <option value="{{ $q->id }}">{{ $q->office_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-prform" tabindex="-1" role="dialog" aria-labelledby="modal-prform" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-success1" id="modal-prform"><b>PERFORMANCE REVIEW FORM</b></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table id="table-form">
                    <thead>
                        <tr>
                            <th rowspan="4" class="text-center" width="125">MFO/PAPs</th>
                            <th rowspan="3" class="text-center" width="100">Success Indicators</th>
                            <th colspan="2" class="text-center" width="100">Evidence</th>
                            <th rowspan="4" class="text-center" width="70">Allotted Budget</th>
                            <th rowspan="4" class="text-center" width="70">Division/ Individuals Accountable</th>
                            <th rowspan="2" colspan="4" class="text-center border-b-n"></th>
                            <th rowspan="4" class="text-center">Remarks/<br>Accomplishment</th>
                        </tr>
                        <tr>
                            <th rowspan="3" class="text-center" width="80">Individual Support Documents</th>
                            <th rowspan="3" class="text-center" width="80">Report of Supervisor/ Other Offices</th>
                        </tr>
                        <tr>
                            <th class="b-none text-center" colspan="4" width="135" height="30">Rating Guide/Accomplishment</th>
                        </tr>
                        <tr>
                            <th>(Targets + Measures)</th>
                            <th class="text-center" width="135">Q</th>
                            <th class="text-center" width="135">E</th>
                            <th class="text-center" width="135">T</th>
                            <th class="text-center" width="135">A</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-form">
                        <tr>
                            <td><b>STRATEGIC PRIORITY (15%)</b></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="b-none text-left"> <i class="fas fa-plus pl-1"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createOpcrModal" tabindex="-1" role="dialog" aria-labelledby="createOpcrModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-success1" id="createOpcrModalLabel"><b>PERFORMANCE REVIEW</b></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <form id="uploadForm" method="POST" action="{{ request()->is('spms/*') ? route('create-opcr') : '' }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-row" id="newrow">
                                <div class="form-group col-md-12 row0" style="margin-bottom: -5px;">
                                    <label for="mfo" class="text-success1">YEAR</label>
                                </div>
                                <div class="form-group col-md-12 row0">
                                    <input type="number" name="year" class="form-control form-control-sm text-center text-success1" style="font-size: 18px !important; font-weight: 900;" value="{{ date('Y') }}" min="2024" max="2050" step="1" placeholder="YYYY">
                                </div>                                
                                <div class="form-group col-md-10 row0">
                                    <label for="mfo" class="text-success1">MFO</label>
                                    <input type="hidden" name="user_id" class="form-control form-control-sm" value="{{ auth()->guard($guard)->user()->id }}" required>
                                    <input type="hidden" name="folder_id" class="form-control form-control-sm" value="{{ request()->is('spms/*') ? $folder->id : '' }}" required>
                                    <input type="text" name="mfo[]" class="form-control form-control-sm" id="mfo" value="CORE FUNCTIONS" placeholder="Enter MFO" autocomplete="off" required readonly>
                                </div>
                                <div class="form-group col-md-2 row0">
                                    <label for="percent" class="text-success1">Percent</label>
                                    <input type="number" name="percent[]" class="form-control form-control-sm text-center" id="percent" value="60" autocomplete="off" placeholder="percent" required>
                                </div>
                                <div class="form-group col-md-10 row1">
                                    <input type="text" name="mfo[]" class="form-control form-control-sm" id="mfo" value="STRATEGIC FUNCTIONS" placeholder="Enter MFO" autocomplete="off" required readonly>
                                </div>
                                <div class="form-group col-md-2 row1">
                                    <input type="number" name="percent[]" class="form-control form-control-sm text-center" id="percent" value="20" autocomplete="off" placeholder="percent" required>
                                </div>
                                <div class="form-group col-md-10 row2">
                                    <input type="text" name="mfo[]" class="form-control form-control-sm" id="mfo" value="SUPPORT FUNCTIONS" placeholder="Enter MFO" autocomplete="off" required readonly>
                                </div>
                                <div class="form-group col-md-2 row2">
                                    <input type="number" name="percent[]" class="form-control form-control-sm text-center" id="percent" value="20" autocomplete="off" placeholder="percent" required>
                                </div>
                                {{-- <div class="form-group col-md-1 row2">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteRow('row2')"><i class="fas fa-times"></i></button>
                                </div> --}}
                            </div>
                            <div class="form-row">
                                <div class="col-md-12 mt-2 text-right">
                                    {{-- <button class="btn btn-secondary btn-sm" type="button" id="addRow"><i class="fas fa-plus fa-xs"></i> Add Row</button> --}}
                                    <button class="btn btn-success btn-sm" type="submit"><i class="fas fa-save"></i> Save</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editFilerModal" tabindex="-1" role="dialog" aria-labelledby="createFolderModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createFolderModalLabel">Rename File</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('document-update') }}">
                    @csrf
                    <div class="form-group">
                        <input type="hidden" name="file_id" id="file-id">
                        <input type="text" class="form-control" id="file-name" name="file_name" placeholder="File Name" autocomplete="off" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endif