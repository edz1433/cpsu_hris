@extends('layouts.master')

@section('body')
<style>
    .custom-label {
        width: 45px;
        padding: 0px;
        padding-left: 5px;
        text-align: center; /* Center align the text */
    }
</style>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="card-header border-0 pb-0">
                        <div class="card-tools d-flex align-items-center w-100 justify-content-between">
                            <div class="input-group input-group-sm" style="width: 300px;">
                                <input type="text" name="table_search" id="empSearchInput" class="form-control" placeholder="Search employee..." autocomplete="off">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-default" id="btnEmpSearch">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <a href="{{ route('empQr') }}" target="_blank" class="btn btn-outline-primary btn-sm mr-1">
                                    <i class="fas fa-qrcode"></i> 
                                </a>
                                <a href="{{ route('genEmp') }}" target="_blank" class="btn btn-outline-danger btn-sm mr-1">
                                    <i class="fas fa-file-pdf"></i> 
                                </a>
                                <a href="{{ route('empAdd') }}" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-user-plus"></i> ADD NEW
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-3">
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-collapsed table-hover" id="employeeTable">
                                <thead>
                                    <tr>
                                        <th>NO.</th>
                                        <th>Full Name</th>
                                        <th>Emp_ID</th> 
                                        <th>Campus</th>
                                        <th>Status</th>
                                        <th>Email</th>
                                        <th>Service</th>
                                        <th>Date Hired</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead> 
                                <tbody id="employeeTableBody">
                                    @include('emp.partials.employee_rows')
                                </tbody> 
                            </table>
                        </div>
                        <div id="empBatchStatusContainer" class="p-2 border-top d-flex justify-content-between align-items-center bg-light">
                            <span id="empBatchInfoText" class="text-muted" style="font-size: 0.875rem;">
                                Showing <strong id="empLoadedCount">{{ count($employee) }}</strong> of <strong id="empTotalCount">{{ $totalCount }}</strong> employees
                            </span>
                            <div class="d-flex align-items-center">
                                <div id="empBatchLoadingSpinner" class="spinner-border spinner-border-sm text-primary mr-2 d-none" role="status">
                                    <span class="sr-only">Loading batch...</span>
                                </div>
                                <button id="btnLoadNextEmpBatch" class="btn btn-outline-primary btn-sm {{ $hasMore ? '' : 'd-none' }}">
                                    <i class="fas fa-download mr-1"></i> Load Next Batch
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="officialTime">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">    
            <div class="card-header">
                <h2 class="card-title text-success1">
                    <b>OFFICIAL WORKING HOURS</b>
                </h2>
            </div>        
            <div class="card-body bg-form">
                <form class="form-horizontal add-form" action="{{ route('OfficialTimeCreate') }}" method="POST">
                    @csrf
                    <div class="form-group mtop">
                        {{-- Monday --}}
                        <div class="form-row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <div class="input-group">
                                        <input type="hidden" name="empid" class="form-control form-control-sm">
                                        <div class="input-group-append">
                                            <span class="input-group-text custom-label"><b>MON.</b></span>
                                        </div>
                                        <input type="time" name="mon_mornin" class="form-control form-control-sm" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="mon_mornout" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="mon_noonin" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="mon_noonout" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        {{-- Tuesday --}}
                        <div class="form-row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-append">
                                            <span class="input-group-text custom-label"><b>TUE.</b></span>
                                        </div>
                                        <input type="time" name="tue_mornin" class="form-control form-control-sm" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="tue_mornout" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="tue_noonin" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="tue_noonout" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        {{-- Wednesday --}}
                        <div class="form-row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-append">
                                            <span class="input-group-text custom-label"><b>WED.</b></span>
                                        </div>
                                        <input type="time" name="wed_mornin" class="form-control form-control-sm" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="wed_mornout" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="wed_noonin" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="wed_noonout" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        {{-- Thursday --}}
                        <div class="form-row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-append">
                                            <span class="input-group-text custom-label"><b>THU.</b></span>
                                        </div>
                                        <input type="time" name="thu_mornin" class="form-control form-control-sm" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="thu_mornout" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="thu_noonin" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="thu_noonout" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        {{-- Friday --}}
                        <div class="form-row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-append">
                                            <span class="input-group-text custom-label"><b>FRI.</b></span>
                                        </div>
                                        <input type="time" name="fri_mornin" class="form-control form-control-sm" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="fri_mornout" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="fri_noonin" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-3">
                                <input type="time" name="fri_noonout" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        
                        <div class="form-row" style="float: right;">
                            <div class="col-md-12">
                                <button class="btn btn-success"><i class="fas fa-save"></i> SAVE</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="toggleConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">Confirm Action</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body p-4" id="confirmMessage" style="font-size: 16px;"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmToggle">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>
<script>
let pendingCheckbox = null;
let pendingEmpId = null;
let pendingNewState = null;

function openToggleDialog(checkbox, fullname, empId) {
    pendingNewState = checkbox.checked;
    pendingEmpId = empId;
    pendingCheckbox = checkbox;
    checkbox.checked = !pendingNewState;
    const action = pendingNewState ? "enable" : "disable";
    document.getElementById("confirmMessage").innerHTML =
        "Are you sure you want to <b>" + action + "</b> this employee's account?<br><br>" +
        "<span class='text-dark font-weight-bold' style='font-size:18px;'>" + fullname + "</span>";
    $("#toggleConfirmModal").modal("show");
}

document.getElementById("confirmToggle").onclick = function () {
    $("#toggleConfirmModal").modal("hide");
    pendingCheckbox.checked = pendingNewState;
    toggleStat(pendingNewState, pendingEmpId);
    pendingCheckbox = null;
};
</script>

<script>
    const empRouteBase = "{{ url('employees') }}";

    let empState = {
        page: {{ $page ?? 1 }},
        limit: {{ $limit ?? 25 }},
        total: {{ $totalCount ?? 0 }},
        hasMore: {{ isset($hasMore) && $hasMore ? 'true' : 'false' }},
        isLoading: false,
        search: ''
    };

    function loadEmpBatch(reset = false) {
        if (empState.isLoading) return;
        
        if (reset) {
            empState.page = 1;
            empState.hasMore = false;
            $('#employeeTableBody').html('<tr><td colspan="10" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>Loading employees...</td></tr>');
        }
        
        if (!reset && !empState.hasMore) return;
        
        empState.isLoading = true;
        $('#empBatchLoadingSpinner').removeClass('d-none');
        $('#btnLoadNextEmpBatch').prop('disabled', true);
        
        $.ajax({
            url: empRouteBase,
            type: 'GET',
            data: {
                ajax: 1,
                page: empState.page,
                limit: empState.limit,
                search: empState.search
            },
            dataType: 'json',
            success: function(res) {
                empState.isLoading = false;
                $('#empBatchLoadingSpinner').addClass('d-none');
                
                if (res.success) {
                    if (reset) {
                        $('#employeeTableBody').html(res.html);
                    } else {
                        $('#employeeTableBody').append(res.html);
                    }
                    
                    empState.total = res.total;
                    empState.hasMore = res.has_more;
                    
                    let loaded = $('#employeeTableBody tr:not(.no-records)').length;
                    $('#empLoadedCount').text(loaded);
                    $('#empTotalCount').text(empState.total);
                    
                    if (empState.hasMore) {
                        $('#btnLoadNextEmpBatch').removeClass('d-none').prop('disabled', false);
                    } else {
                        $('#btnLoadNextEmpBatch').addClass('d-none');
                    }
                }
            },
            error: function(err) {
                empState.isLoading = false;
                $('#empBatchLoadingSpinner').addClass('d-none');
                $('#btnLoadNextEmpBatch').prop('disabled', false);
                console.error('Failed to load employee batch:', err);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        $('#btnLoadNextEmpBatch').on('click', function() {
            if (empState.hasMore && !empState.isLoading) {
                empState.page++;
                loadEmpBatch(false);
            }
        });

        $('.table-responsive').on('scroll', function() {
            let container = $(this);
            if (container.scrollTop() + container.innerHeight() >= container[0].scrollHeight - 60) {
                if (empState.hasMore && !empState.isLoading) {
                    empState.page++;
                    loadEmpBatch(false);
                }
            }
        });

        let searchTimer;
        $('#empSearchInput').on('input', function() {
            clearTimeout(searchTimer);
            let val = $(this).val();
            searchTimer = setTimeout(function() {
                empState.search = val;
                loadEmpBatch(true);
            }, 300);
        });
    });
</script>
@endsection