<style>
    .modal-custom {
        max-width: 40%;
        height: 40%;
        margin: 30px auto;
    }
</style>
<div class="modal fade" id="createOpcrMfoModal" tabindex="-1" role="dialog" aria-labelledby="createOpcrMfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-custom modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-success1" id="createOpcrMfoModalLabel"><b id="opcr-cat-text"></b></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <form id="uploadForm" method="POST" action="{{ request()->is('spms/*') ? route('update-opcr-mfo') : '' }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="opcr-cat" id="opcr-cat">
                            <input type="hidden" name="opcr-id" id="opcr-id">

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
<div class="modal fade" id="opcrMfoData" tabindex="-1" role="dialog" aria-labelledby="opcrMfoDataLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-success1" id="opcrMfoDataLabel"><b id="functions">PERFORMANCE REVIEW</b></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" data-id="1">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <form id="uploadForm" method="POST" action="{{ request()->is('spms/*') ? route('create-opcr-mfo-data') : '' }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="opcr_mfo_id" id="opcr-mfo-id">
                            <input type="hidden" name="opcrdata_id" id="opcrdata_id">
                            <div class="form-row align-items-center">
                                <div class="form-group col-md-4">
                                    <label class="text-success1">QUARTER</label>
                                    <select type="text" class="form-control" name="category" id="category">
                                        <option value="All">All</option>
                                        <option value="1">1ST HALF</option>
                                        <option value="2">2ND HALF</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="text-success1">DIVISION/ INDIVIDUALS ACCOUNTABLE</label>
                                    <input type="text" class="form-control p-3" name="opcr_by" id="opcr_by" autocomplete="off">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="text-success1">MFO / PAP's</label>
                                    <input type="text" class="form-control p-3" name="mfo" id="mfo" autocomplete="off">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">TARGETS </label>
                                    <textarea name="target" rows="3" class="form-control form-control-sm" id="target" autocomplete="off"></textarea>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">MEASURE</label>
                                    <textarea name="measure" rows="3" class="form-control form-control-sm" id="measure" autocomplete="off"
                                        oninput="this.value = this.value.replace(/[^\d\n.,]/g, '');"></textarea>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">INDIVIDUAL SUPPORT DOCUMENTS</label>
                                    <textarea name="in_support" rows="3" class="form-control form-control-sm" id="in_support" autocomplete="off"></textarea>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">REPORT OF SUPERVISOR / OTHER OFFICES</label>
                                    <textarea name="report_sup" rows="3" class="form-control form-control-sm" id="report_sup" autocomplete="off"></textarea>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">DIVISION / INDIVIDUALS ACCOUNTABLE</label>
                                    <textarea name="div_account" rows="3" class="form-control form-control-sm" id="div_account" autocomplete="off"></textarea>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">QUALITY</label>
                                    <textarea name="quality" rows="3" class="form-control form-control-sm" id="quality" autocomplete="off"></textarea>
                                </div>
                                <div class="form-group col-md-3">   
                                    <label class="text-success1">EFFICIENCY</label>
                                    <textarea name="efficiency" rows="3" class="form-control form-control-sm" id="efficiency" autocomplete="off"></textarea>
                                </div>
                                <div class="form-group col-md-3">
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
<div class="modal fade" id="opcrModal" tabindex="-1" aria-labelledby="opcrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="opcrModalLabel">OPCR Entry Options</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <input type="hidden" id="opcr_id"> 

                <p>What do you want to do?</p>

                <button class="btn btn-primary btn-block mb-2" onclick="editOpcrData()">Edit</button>
                <button class="btn btn-danger btn-block" onclick="deleteOpcrData()">Delete</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="asign-to-dpcr" aria-labelledby="opcrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" style="width: 400px !important;">
        <div class="modal-content">
            <form method="POST" action="{{ route('assignOpcr') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="opcrModalLabel">Asign (OPCR)</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body text-center">
                    <input type="hidden" name="opcrid" id="opcr-mfo-data-id">
                    <input type="hidden" name="count" id="count">
                    <select class="form-control form-control-sm select2" name="empid[]" id="employee" required multiple>
                        <option value="C:2">All Dean</option>
                        <option value="C:3">All Campus Ad</option>
                        <option value="C:4">All Office Head</option>
                        <option value="C:5">All Director</option>
                        <option value="C:6">All Staff</option>
                        <option value="C:7">All Faculty</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @if(isset($employee) && $employee && $emp->emp_ID == $employee->emp_ID) selected @endif>
                                {{ $emp->lname }}
                                {{ $emp->prefix }}
                                {{ $emp->fname }}
                                {{ isset($emp->mname) ?substr($emp->mname, 0, 1).'.' : '' }}
                            </option>
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
                <h5 class="modal-title" id="setupModalLabel">Setup Asignatories</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="setupForm">
                    <div class="row">
                        @php
                            $groupedAsignatories = $selectedEmployees->groupBy('label');
                        @endphp
                        @foreach ($groupedAsignatories as $label => $asignatories)
                            <div class="col-md-12 mb-3">
                                <label>{{ $label ?? 'Employee' }}</label>
                                @foreach ($asignatories as $index => $asignatory)
                                    <div class="row mb-2">
                                        <div class="col-md-4">
                                            <select class="form-control form-control-sm select2" name="employee{{ $loop->parent->index + $index + 1 }}" id="employee{{ $loop->parent->index + $index + 1 }}" required>
                                                <option value="">Select Employee</option>
                                                @foreach($employees as $emp)
                                                    @php
                                                        $fullName = $emp->fname . ' ' .
                                                        ($emp->mname ? strtoupper(substr($emp->mname, 0, 1)) . '. ' : '') .
                                                        $emp->lname .
                                                        ($emp->suffixes ? ', ' . $emp->suffixes : '');
                                                    @endphp
                                                    <option value="{{ $emp->emp_ID }}" 
                                                        @if($emp->emp_ID == $asignatory->empid) selected @endif>
                                                        {{ $fullName }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control form-control-sm" name="designation{{ $loop->parent->index + $index + 1 }}" id="designation{{ $loop->parent->index + $index + 1 }}" value="{{ $asignatory->designation ?? '' }}" placeholder="Designation">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </form>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" onclick="saveSetup()">Save changes</button>
            </div>
        </div>
    </div>
</div>