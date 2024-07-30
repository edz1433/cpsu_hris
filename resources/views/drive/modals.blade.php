
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
    font-size: 12px;
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
                            <th rowspan="3" class="text-center" width="210">MFO</th>
                            <th rowspan="3" class="text-center" width="300">Success Indicators <br>(Targets + Measures)</th>
                            <th rowspan="3" class="text-center" width="300">Actual Accomplishments /<br>Expenses</th>
                            <th class="text-center" colspan="4">Rating</th>
                            <th rowspan="3" class="text-center"  width="80">REMARKS</th>
                            <th rowspan="3" class="text-center b-none"></th>
                        </tr>
                        <tr>
                            <th class="text-center" width="60">Q</th>
                            <th class="text-center" width="60">E</th>
                            <th class="text-center" width="60">T</th>
                            <th class="text-center" width="60">A</th>
                        </tr>
                        <tr>
                            <th class="text-center" style="font-size: 10px;">Quality</th>
                            <th class="text-center" style="font-size: 10px;">Efficiency</th>
                            <th class="text-center" style="font-size: 10px;">Timeliness</th>
                            <th class="text-center" style="font-size: 10px;">Average</th>
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
                            <td class="b-none text-left"> <i class="fas fa-plus pl-1"></td>
                        </tr>
                         <tr>
                            <td><b>CORE FUNCTIONS (80%)</b></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="b-none text-left"> <i class="fas fa-plus pl-1"></td>
                        </tr>
                        <tr>
                            <td><b>SUPPORT FUNCTIONS (5%)</b></td>
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

<div class="modal fade" id="uploadFileModal" tabindex="-1" role="dialog" aria-labelledby="uploadFileModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-success1" id="uploadFileModalLabel"><b>PERFORMANCE REVIEW</b></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <form id="uploadForm" method="POST" action="{{ request()->is('spms/*') ? route('createpr') : '' }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-row" id="newrow">
                                <div class="form-group col-md-8 row0">
                                    <label for="mfo" class="text-success1">MFO</label>
                                    <input type="hidden" name="user_id" class="form-control form-control-sm" value="{{ auth()->guard($guard)->user()->id }}" required>
                                    <input type="hidden" name="folder_id" class="form-control form-control-sm" value="{{ request()->is('spms/*') ? $folder->id : '' }}" required>
                                    <input type="text" name="mfo[]" class="form-control form-control-sm" id="mfo" placeholder="Enter MFO" required>
                                </div>
                                <div class="form-group col-md-3 row0">
                                    <label for="percent" class="text-success1">Percent</label>
                                    <input type="number" name="percent[]" class="form-control form-control-sm" id="percent" placeholder="percent" required>
                                </div>
                                <div class="form-group col-md-1 row0">
                                    <label for="percent" class="">&nbsp;</label>
                                </div>
                                <div class="form-group col-md-8 row1">
                                    <input type="text" name="mfo[]" class="form-control form-control-sm" id="mfo" placeholder="Enter MFO" required>
                                </div>
                                <div class="form-group col-md-3 row1">
                                    <input type="number" name="percent[]" class="form-control form-control-sm" id="percent" placeholder="percent" required>
                                </div>
                                <div class="form-group col-md-1 row1">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteRow('row1')"><i class="fas fa-times"></i></button>
                                </div>
                                <div class="form-group col-md-8 row2">
                                    <input type="text" name="mfo[]" class="form-control form-control-sm" id="mfo" placeholder="Enter MFO" required>
                                </div>
                                <div class="form-group col-md-3 row2">
                                    <input type="number" name="percent[]" class="form-control form-control-sm" id="percent" placeholder="percent" required>
                                </div>
                                <div class="form-group col-md-1 row2">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteRow('row2')"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-12 mt-2 text-right">
                                    <button class="btn btn-secondary btn-sm" type="button" id="addRow"><i class="fas fa-plus fa-xs"></i> Add Row</button>
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