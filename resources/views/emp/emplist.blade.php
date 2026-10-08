@extends('layouts.master')

@section('body')
@php
    // [day prefix used by the form fields, label]
    $workDays = [
        ['mon', 'Monday'],
        ['tue', 'Tuesday'],
        ['wed', 'Wednesday'],
        ['thu', 'Thursday'],
        ['fri', 'Friday'],
    ];
@endphp
<div class="container-fluid dash">
    <div class="dtr-head">
        <div>
            <h1>Employees</h1>
            <p>Everyone on record, with their status, length of service and account access.</p>
        </div>
        <div class="lv-actions emp-head-actions">
            <a href="{{ route('empQr') }}" target="_blank" rel="noopener" class="lv-btn" title="Print employee QR codes">
                <i class="fas fa-qrcode"></i> QR codes
            </a>
            <a href="{{ route('genEmp') }}" target="_blank" rel="noopener" class="lv-btn" title="Generate the employee list as PDF">
                <i class="fas fa-file-pdf"></i> PDF list
            </a>
            <a href="{{ route('empAdd') }}" class="lv-btn is-primary">
                <i class="fas fa-user-plus"></i> Add employee
            </a>
        </div>
    </div>

    <div class="dash-card">
        <div class="dash-card-header flex-wrap">
            <h5><i class="fas fa-users" style="color: var(--cpsu-green-600);"></i>Employee list</h5>
            <label class="emp-search mb-0" for="empSearchInput">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" name="table_search" id="empSearchInput" placeholder="Search name, ID, position, email..." autocomplete="off" aria-label="Search employees">
            </label>
        </div>
        <div class="table-responsive emp-scroll">
            <table class="table table-hover lv-table emp-table" id="employeeTable">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>Employee</th>
                        <th>Emp ID</th>
                        <th>Campus</th>
                        <th>Status</th>
                        <th>Email</th>
                        <th>Service</th>
                        <th>Date hired</th>
                        <th class="text-center">Account</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="employeeTableBody">
                    @include('emp.partials.employee_rows')
                </tbody>
            </table>
        </div>
        <div id="empBatchStatusContainer" class="emp-footer">
            <span id="empBatchInfoText">
                Showing <strong id="empLoadedCount">{{ count($employee) }}</strong> of <strong id="empTotalCount">{{ $totalCount }}</strong> employees
            </span>
            <div class="d-flex align-items-center">
                <div id="empBatchLoadingSpinner" class="spinner-border spinner-border-sm mr-2 d-none" style="color: var(--cpsu-green-600);" role="status">
                    <span class="sr-only">Loading batch...</span>
                </div>
                <button type="button" id="btnLoadNextEmpBatch" class="lv-btn {{ $hasMore ? '' : 'd-none' }}">
                    <i class="fas fa-angle-double-down"></i> Load more
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade ev-modal" id="officialTime" tabindex="-1" role="dialog" aria-labelledby="officialTimeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form class="dtr-form add-form" action="{{ route('OfficialTimeCreate') }}" method="POST">
                @csrf
                <input type="hidden" name="empid">
                <div class="modal-header">
                    <h5 class="modal-title" id="officialTimeLabel"><i class="fas fa-clock"></i>Official working hours</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="lv-note mb-3">The schedule the employee's DTR is checked against, Monday to Friday.</p>
                    <div class="emp-hours">
                        <div class="emp-hours-head">
                            <span></span>
                            <span>Morning in</span>
                            <span>Morning out</span>
                            <span>Afternoon in</span>
                            <span>Afternoon out</span>
                        </div>
                        @foreach($workDays as [$day, $label])
                            <div class="emp-hours-row">
                                <span class="emp-hours-day">{{ $label }}</span>
                                <input type="time" name="{{ $day }}_mornin" class="form-control" aria-label="{{ $label }} morning in" required>
                                <input type="time" name="{{ $day }}_mornout" class="form-control" aria-label="{{ $label }} morning out" required>
                                <input type="time" name="{{ $day }}_noonin" class="form-control" aria-label="{{ $label }} afternoon in" required>
                                <input type="time" name="{{ $day }}_noonout" class="form-control" aria-label="{{ $label }} afternoon out" required>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lv-btn" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="lv-btn is-primary"><i class="fas fa-save"></i> Save hours</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade ev-modal" id="toggleConfirmModal" tabindex="-1" role="dialog" aria-labelledby="toggleConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="toggleConfirmLabel"><i class="fas fa-user-shield"></i>Change account access</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body pb-3" id="confirmMessage" style="font-size: 14px;"></div>
            <div class="modal-footer">
                <button type="button" class="lv-btn" data-dismiss="modal">Cancel</button>
                <button type="button" class="lv-btn is-primary" id="confirmToggle">Confirm</button>
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
    const confirmBtn = document.getElementById("confirmToggle");
    confirmBtn.textContent = pendingNewState ? "Enable account" : "Disable account";
    confirmBtn.classList.toggle("is-primary", pendingNewState);
    confirmBtn.classList.toggle("is-danger", !pendingNewState);
    document.getElementById("confirmMessage").innerHTML =
        "Are you sure you want to <b>" + action + "</b> this employee's account?" +
        "<div class='emp-confirm-name'>" + fullname + "</div>";
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
            $('#employeeTableBody').html('<tr class="no-records"><td colspan="10"><div class="dtr-empty"><div class="dtr-empty-icon"><i class="fas fa-spinner fa-spin"></i></div><h6>Loading employees...</h6></div></td></tr>');
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

        $('.emp-scroll').on('scroll', function() {
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
