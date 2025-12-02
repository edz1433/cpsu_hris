<style>
    .modal-custom {
        max-width: 40%;
        height: 40%;
        margin: 30px auto;
    }
</style> 
<div class="modal fade" id="createDpcrMfoModal" tabindex="-1" role="dialog" aria-labelledby="createDpcrMfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-custom modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-success1" id="createDpcrMfoModalLabel"><b id="dpcr-cat-text"></b></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <form id="uploadForm" method="POST" action="{{ route('update-dpcr-mfo') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="dpcr-cat" id="dpcr-cat">
                            <input type="hidden" name="dpcr-id" id="dpcr-id">

                            <div class="form-row" id="form-data">

                            </div>

                            <div class="form-row">
                                <div class="col-md-12 mt-2 text-right">
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
<div class="modal fade" id="dpcrMfoData" tabindex="-1" role="dialog" aria-labelledby="dpcrMfoDataLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-success1" id="dpcrMfoDataLabel"><b id="functions">PERFORMANCE REVIEW</b></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" data-id="1">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <form id="uploadForm" method="POST" action="{{ route('create-dpcr-mfo-data') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="dpcr_mfo_id" id="dpcr_mfo_id">
                            <input type="hidden" name="dpcrdata_id" id="dpcrdata_id">
                            <input type="hidden" name="user_id" id="user_id" value="{{ $empid }}">
                            <div class="form-row align-items-center">
                                <div class="form-group col-md-3">
                                    <label class="text-success1">QUARTER</label>
                                    <select type="text" class="form-control" name="category" id="category">
                                        <option value="All">All</option>
                                        <option value="1" selected>1ST HALF</option>
                                        <option value="2">2ND HALF</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">DIVISION/ INDIVIDUALS ACCOUNTABLE</label>
                                    <input type="text" class="form-control p-3" name="div_account" id="div_account" autocomplete="off">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">MFO / PAP's</label>
                                    <input type="text" class="form-control p-3" name="mfo" id="mfo" autocomplete="off">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">REPORT OF SUPERVISOR / OTHER OFFICES</label>
                                    <input name="report_sup" rows="3" class="form-control" id="report_sup" autocomplete="off">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="text-success1">TARGETS </label>
                                    <textarea name="target" rows="3" class="form-control form-control-sm" id="target" autocomplete="off"></textarea>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="text-success1">MEASURE</label>
                                    <textarea name="measure" rows="3" class="form-control form-control-sm" id="measure" autocomplete="off"
                                        oninput="this.value = this.value.replace(/[^\d\n.,]/g, '');"></textarea>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="text-success1">Link to Source</label>
                                    <textarea name="in_support" rows="3" class="form-control form-control-sm" id="in_support" autocomplete="off" {{ (in_array($userid, $pmtsmember) || $guard == 'web') ? '' : 'readonly' }}></textarea>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="text-success1">QUALITY</label>
                                    <textarea name="quality" rows="3" class="form-control form-control-sm" id="quality" autocomplete="off"></textarea>
                                </div>
                                <div class="form-group col-md-4">   
                                    <label class="text-success1">EFFICIENCY</label>
                                    <textarea name="efficiency" rows="3" class="form-control form-control-sm" id="efficiency" autocomplete="off"></textarea>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="text-success1">TIMELINESS</label>
                                    <textarea name="timeliness" rows="3" class="form-control form-control-sm" id="timeliness" autocomplete="off"></textarea>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-12 mt-2 text-right">
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
<!-- Modal -->
<div class="modal fade" id="dpcrModal" tabindex="-1" aria-labelledby="dpcrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dpcrModalLabel">DPCR Entry Options</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <input type="hidden" id="dpcr_id"> 
                <p>What do you want to do?</p>

                <button class="btn btn-primary btn-block mb-2" onclick="editDpcrData()">Edit</button>
                <button class="btn btn-danger btn-block" onclick="deleteDpcrData()">Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="asign-to-dpcr" aria-labelledby="dpcrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" style="width: 400px !important;">
        <div class="modal-content">
            <form method="POST" action="{{ route('assignDpcr') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="dpcrModalLabel">Asign</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body text-center">
                    <input type="hidden" name="dpcrid" id="dpcr-mfo-data-id">
                    <input type="hidden" name="count" id="count">
                    <label for="Employee">Employee</label>
                    <select class="form-control form-control-sm select2" name="empid[]" id="employee" required multiple>
                        @if($folder == 1)
                            <option value="C:2">All Dean</option>
                            <option value="C:3">All Campus Ad</option>
                            <option value="C:4">All Office Head</option>
                            <option value="C:5">All Director</option> 
                        @else
                            @if($guard == "web" || in_array($userid, $pmtsmember ?? []))
                                {{-- <option value="C:6">All Staff</option>
                                <option value="C:7">All Faculty</option> --}}
                            @endif
                        @endif
                        @foreach($employees as $emp)
                            @if($emp->emp_id != $dempid)
                                <option value="{{ $emp->emp_id }}" 
                                    @if(isset($employee) && $employee && $emp->emp_id == $employee->emp_ID) selected @endif>
                                    {{ $emp->lname }}
                                    {{ $emp->prefix }}
                                    {{ $emp->fname }}
                                    {{ isset($emp->mname) ? substr($emp->mname, 0, 1).'.' : '' }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <div class="form-row">
                        <div class="col-md-12 mt-2 text-right">
                            <button class="btn btn-success btn-sm" type="submit" id="asign-to-dpcr-btn"><i class="fas fa-save"></i> Save</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="asign-to-ipcr" aria-labelledby="dpcrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" style="width: 600px !important;">
        <div class="modal-content">
            <form method="POST" action="{{ route('assignDpcr') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="dpcrModalLabel">Asign</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="dpcrid" id="dpcr-mfo-data-id">
                    <input type="hidden" name="count" id="count1">
                    <label for="target">Target</label>
                    <textarea class="form-control mb-2" id="ipcr-target" name="target" cols="30" rows="5"></textarea>
                    <label for="Employee">Employee</label>
                    <select class="form-control form-control-sm select2" name="empid[]" id="employees" required multiple>
                        @foreach($employees as $emp)
                            @if($emp->id != $dempid)
                                <option value="{{ $emp->id }}" 
                                    @if(isset($employee) && $employee && $emp->id == $employee->emp_ID) selected @endif>
                                    {{ $emp->lname }}
                                    {{ $emp->prefix }}
                                    {{ $emp->fname }}
                                    {{ isset($emp->mname) ? substr($emp->mname, 0, 1).'.' : '' }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <div class="form-row">
                        <div class="col-md-12 mt-2 text-right">
                            <button class="btn btn-success btn-sm" type="submit" id="asign-to-dpcr-btn"><i class="fas fa-save"></i> Save</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Setup Asignatories -->
<div class="modal fade" id="setupModal" tabindex="-1" role="dialog" aria-labelledby="setupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" style="width: 900px !important;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="setupModalLabel">Asignatories</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="setupForm" action="{{ route('updateAsignatories') }}" method="POST">
                    @csrf
                    <input type="hidden" name="pr_number" value="{{ $selectedEmployees->first()->pr_number ?? '' }}">
                    <div class="row">
                        @php
                            $groupedAsignatories = $selectedEmployees->groupBy('label');
                        @endphp
                        @foreach ($groupedAsignatories as $label => $asignatories)
                            <div class="col-md-12 mb-3">
                                <label>{{ $label ?? 'Employee' }}</label>
                                @foreach ($asignatories as $asignatory)
                                    @php
                                        $rowId = $asignatory->id;
                                    @endphp
                                    <div class="row mb-2">
                                        <div class="col-md-4">
                                            <select class="form-control form-control-sm select2" name="employee[{{ $rowId }}]" id="employee{{ $rowId }}" required>
                                                <option value="">Select Employee</option>
                                                @foreach($employeesreg as $emp)
                                                    @php
                                                        $fullName = $emp->fname . ' ' .
                                                        ($emp->mname ? strtoupper(substr($emp->mname, 0, 1)) . '. ' : '') .
                                                        $emp->lname .
                                                        ($emp->suffixes ? ', ' . $emp->suffixes : '');
                                                    @endphp
                                                    <option value="{{ $emp->emp_ID }}" 
                                                        @if($emp->emp_ID == $asignatory->empid) selected @endif>
                                                        {{ ucwords(strtolower($fullName)) }} 
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control form-control-sm" 
                                                name="designation[{{ $rowId }}]" 
                                                value="{{ ucwords(strtolower($asignatory->designation)) }}" 
                                                placeholder="Designation" autocomplete="off">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>