<style>
    .modal-custom {
        max-width: 40%;
        height: 40%;
        margin: 30px auto;
    }
</style> 
<div class="modal fade" id="createIpcrMfoModal" tabindex="-1" role="dialog" aria-labelledby="createIpcrMfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-custom modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-success1" id="createIpcrMfoModalLabel"><b id="ipcr-cat-text"></b></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <form id="uploadForm" method="POST" action="{{ route('update-ipcr-mfo') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="ipcr-cat" id="ipcr-cat">
                            <input type="hidden" name="ipcr-id" id="ipcr-id">

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
<div class="modal fade" id="ipcrMfoData" tabindex="-1" role="dialog" aria-labelledby="ipcrMfoDataLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-success1" id="ipcrMfoDataLabel"><b id="functions">PERFORMANCE REVIEW</b></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" data-id="1">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <form id="uploadForm" method="POST" action="{{ route('create-ipcr-mfo-data') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="ipcr_mfo_id" id="ipcr_mfo_id">
                            <input type="hidden" name="ipcrdata_id" id="ipcrdata_id">
                            <input type="hidden" name="user_id" id="user_id" value="{{ $empid }}">
                            <div class="form-row align-items-center">
                                <div class="form-group col-md-1">
                                    <label class="text-success1">QUARTER</label>
                                    <select type="text" class="form-control" name="category" id="category">
                                        <option value="All">All</option>
                                        <option value="1" selected>1ST HALF</option>
                                        <option value="2">2ND HALF</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">INDIVIDUAL SUPPORT DOCUMENTS</label>
                                    <input type="text" class="form-control p-3" name="in_support" id="in_support" autocomplete="off">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">REPORT OF SUPERVISOR / OTHER OFFICES</label>
                                    <input type="text" name="report_sup" class="form-control" id="report_sup" autocomplete="off">
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="text-success1">MFO / PAP's</label>
                                    <input type="text" class="form-control p-3" name="mfo" id="mfo" autocomplete="off">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">DIVISION/ INDIVIDUALS ACCOUNTABLE</label>
                                    <input type="text" class="form-control p-3" name="div_account" id="div_account" autocomplete="off">
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
                                <div class="form-group col-md-4">
                                    <label class="text-success1">REMARKS</label>
                                    <textarea name="remarks" rows="3" class="form-control form-control-sm" id="remarks" autocomplete="off"></textarea>
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
<div class="modal fade" id="ipcrModal" tabindex="-1" aria-labelledby="ipcrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ipcrModalLabel">iPCR Entry Options</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <input type="hidden" id="ipcr_id"> 
                <p>What do you want to do?</p>

                <button class="btn btn-primary btn-block mb-2" onclick="editIpcrData()">Edit</button>
                <button class="btn btn-danger btn-block" onclick="deleteIpcrData()">Delete</button>
            </div>
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
                    <input type="hidden" name="pr_number" value="{{ $selectedEmployees->first()->pr_number ?? ($dprnumber ?? '') }}">
                    <input type="hidden" name="spms_type" value="{{ $selectedEmployees->first()->spms_type ?? 'IPCR' }}">
                    <div class="row">
                        @php
                            $groupedAsignatories = $selectedEmployees->groupBy('label');
                            if ($groupedAsignatories->isEmpty()) {
                                $groupedAsignatories = collect(['Employee' => collect()]);
                            }
                        @endphp
                        @foreach ($groupedAsignatories as $label => $asignatories)
                            <div class="col-md-12 mb-3">
                                <label>{{ ($label ?? 'Employee') === 'Approved:' ? 'Final Rating by:' : ($label ?? 'Employee') }}</label>
                                <div id="asignatoryGroup{{ $loop->index }}">
                                @foreach ($asignatories as $asignatory)
                                    @php
                                        $rowId = $asignatory->id;
                                    @endphp
                                    <div class="row mb-2 align-items-center" id="asignatoryRow{{ $rowId }}">
                                        <div class="col-md-3">
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
                                        <div class="col-md-2">
                                            <input type="text" class="form-control form-control-sm"
                                                name="suffixes[{{ $rowId }}]"
                                                value="{{ $asignatory->suffixes }}"
                                                placeholder="Suffix" autocomplete="off">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control form-control-sm" 
                                                name="designation[{{ $rowId }}]" 
                                                value="{{ ucwords(strtolower($asignatory->designation)) }}" 
                                                placeholder="Designation" autocomplete="off">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger btn-sm btn-block delete-asignatory-row" data-row-id="{{ $rowId }}">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm add-asignatory-row" data-target="asignatoryGroup{{ $loop->index }}" data-label="{{ $label ?? 'Employee' }}">
                                    <i class="fas fa-plus"></i> Add
                                </button>
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

<script type="text/template" id="asignatoryEmployeeOptionsTemplate">
    <option value="">Select Employee</option>
    @foreach($employeesreg as $emp)
        @php
            $fullName = $emp->fname . ' ' .
            ($emp->mname ? strtoupper(substr($emp->mname, 0, 1)) . '. ' : '') .
            $emp->lname .
            ($emp->suffixes ? ', ' . $emp->suffixes : '');
        @endphp
        <option value="{{ $emp->emp_ID }}">{{ ucwords(strtolower($fullName)) }}</option>
    @endforeach
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const setupForm = document.getElementById('setupForm');
        const employeeOptionsTemplate = document.getElementById('asignatoryEmployeeOptionsTemplate');
        let newAsignatoryIndex = 0;

        if (!setupForm || !employeeOptionsTemplate) {
            return;
        }

        const escapeAttribute = function (value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        };

        setupForm.addEventListener('click', function (event) {
            const deleteButton = event.target.closest('.delete-asignatory-row');
            const addButton = event.target.closest('.add-asignatory-row');

            if (deleteButton) {
                const rowId = deleteButton.dataset.rowId;
                const row = document.getElementById('asignatoryRow' + rowId);
                const deleteInput = document.createElement('input');

                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_ids[]';
                deleteInput.value = rowId;
                setupForm.appendChild(deleteInput);

                if (row) {
                    row.remove();
                }
            }

            if (addButton) {
                const target = document.getElementById(addButton.dataset.target);
                const label = addButton.dataset.label || 'Employee';
                const row = document.createElement('div');

                newAsignatoryIndex++;
                row.className = 'row mb-2 align-items-center';
                row.id = 'newAsignatoryRow' + newAsignatoryIndex;
                row.innerHTML = `
                    <input type="hidden" name="new_label[]" value="${escapeAttribute(label)}">
                    <div class="col-md-3">
                        <select class="form-control form-control-sm select2" name="new_employee[]" required>
                            ${employeeOptionsTemplate.innerHTML}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control form-control-sm" name="new_suffixes[]" placeholder="Suffix" autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control form-control-sm" name="new_designation[]" placeholder="Designation" autocomplete="off">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-secondary btn-sm btn-block remove-new-asignatory-row" data-row-id="${newAsignatoryIndex}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;

                target.appendChild(row);

                if (window.jQuery && jQuery.fn.select2) {
                    jQuery(row).find('.select2').select2();
                }
            }

            const removeNewButton = event.target.closest('.remove-new-asignatory-row');

            if (removeNewButton) {
                const row = document.getElementById('newAsignatoryRow' + removeNewButton.dataset.rowId);

                if (row) {
                    row.remove();
                }
            }
        });
    });
</script>
