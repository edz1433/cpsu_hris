@extends('layouts.master')

@section('body')
<style>
    .contracts-page .card { border:1px solid var(--cpsu-line, #e4ebe7); border-radius:var(--cpsu-radius, 12px); box-shadow:none; }
    .period-head { display:flex; flex-wrap:wrap; gap:12px; justify-content:space-between; align-items:flex-start; padding:16px 20px; border-bottom:1px solid var(--cpsu-line, #e4ebe7); }
    .period-head h1 { color:var(--cpsu-ink-900, #1c2b24); font-size:1.15rem; font-weight:700; margin:0 0 4px; }
    .period-meta { color:var(--cpsu-ink-600, #56655d); font-size:.875rem; }
    .period-meta span + span::before { content:"·"; margin:0 8px; color:var(--cpsu-ink-400, #8a978f); }
    .period-actions { display:flex; flex-wrap:wrap; gap:6px; }
    .contracts-page .table td, .contracts-page .table th { vertical-align:middle; }
    .contracts-page .btn-cpsu, .contracts-modal .btn-cpsu { background:#187744; border-color:#187744; color:#fff; }
    .contracts-page .btn-cpsu:hover, .contracts-modal .btn-cpsu:hover { background:#146a3b; border-color:#146a3b; color:#fff; }
    .contract-badge { border-radius:999px; display:inline-block; font-size:.75rem; font-weight:600; padding:3px 10px; }
    .contract-badge-open, .contract-badge-active { background:#e3f2e9; color:#146a3b; }
    .contract-badge-signed { background:#fff6d6; color:#7a5d00; }
    .contract-badge-closed { background:#eceff1; color:#56655d; }
    .contract-badge-cancelled { background:#fdecea; color:#a3261b; }
    .contract-row-cancelled td { color:var(--cpsu-ink-400, #8a978f); }
    .contracts-modal .modal-header { background:#187744; color:#fff; }
    .contracts-modal .modal-header .close { color:#fff; opacity:1; }
    .emp-option-meta { color:#8a978f; display:block; font-size:.8rem; }
    .select2-results__option--highlighted .emp-option-meta { color:#e3f2e9; }
</style>

@php
    $counts = $contracts->countBy('status');
    $downloadable = ($counts['active'] ?? 0) + ($counts['signed'] ?? 0);
    $money = fn ($v) => number_format((float) $v, 2);
@endphp

<div class="container-fluid contracts-page">
    <a href="{{ route('contracts.index') }}" class="d-inline-block mb-2 text-success"><i class="fas fa-arrow-left"></i> All contract periods</a>

    @if(session('warning'))
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-1"></i> {{ session('warning') }}</div>
    @endif

    <div class="card">
        <div class="period-head">
            <div>
                <h1>{{ $period->title }}</h1>
                <div class="period-meta">
                    <span>{{ $period->typeLabel() }}</span>
                    <span>{{ $period->start_date->format('F j, Y') }} – {{ $period->end_date->format('F j, Y') }}</span>
                    <span><span class="contract-badge contract-badge-{{ $period->status }}">{{ ucfirst($period->status) }}</span></span>
                    <span>{{ $downloadable }} {{ \Illuminate\Support\Str::plural('employee', $downloadable) }}@if($counts['signed'] ?? 0), {{ $counts['signed'] }} signed @endif @if($counts['cancelled'] ?? 0), {{ $counts['cancelled'] }} cancelled @endif</span>
                </div>
            </div>
            <div class="period-actions">
                @if($period->isOpen())
                    <button class="btn btn-cpsu" data-toggle="modal" data-target="#addEmployeesModal">
                        <i class="fas fa-user-plus"></i> Add Employees
                    </button>
                @endif
                <a href="{{ route('contracts.downloadAll', $period) }}" class="btn btn-light border {{ $downloadable ? '' : 'disabled' }}">
                    <i class="fas fa-file-archive"></i> Download All (ZIP)
                </a>
                <form action="{{ route('contracts.downloadSelected', $period) }}" method="POST" id="downloadSelectedForm" class="d-inline-block">
                    @csrf
                    <button type="submit" class="btn btn-light border" id="downloadSelectedBtn" disabled>
                        <i class="fas fa-download"></i> Download Selected (<span id="selectedCount">0</span>)
                    </button>
                </form>
                @if($period->isOpen())
                    <form action="{{ route('contracts.close', $period) }}" method="POST" class="d-inline-block js-confirm"
                          data-confirm="Close this period? Contracts can still be downloaded, but nothing can be edited.">
                        @csrf
                        <button class="btn btn-light border"><i class="fas fa-lock"></i> Close Period</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="contractsTable" class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" style="width:32px;"><input type="checkbox" id="checkAll" title="Select all"></th>
                            <th>Reference No.</th>
                            <th>Employee</th>
                            <th>Campus</th>
                            <th>Position</th>
                            <th class="text-right">Monthly Rate</th>
                            <th class="text-right">Daily Deduction</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contracts as $contract)
                            @php $campus = $campuses->get($contract->campus_id); @endphp
                            <tr class="contract-row-{{ $contract->status }}">
                                <td class="text-center"><input type="checkbox" class="js-row-check" value="{{ $contract->id }}"></td>
                                <td class="text-nowrap">{{ $contract->reference_no }}</td>
                                <td class="font-weight-bold">{{ $contract->employee_name }}</td>
                                <td>{{ $campus->campus_abbr ?? '—' }}</td>
                                <td>{{ $contract->position }}</td>
                                <td class="text-right" data-order="{{ $contract->monthly_rate }}">₱{{ $money($contract->monthly_rate) }}</td>
                                <td class="text-right" data-order="{{ $contract->daily_deduction }}">₱{{ $money($contract->daily_deduction) }}</td>
                                <td class="text-center"><span class="contract-badge contract-badge-{{ $contract->status }}">{{ ucfirst($contract->status) }}</span></td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ route('contracts.contract.download', $contract) }}" class="btn btn-sm btn-cpsu" title="Download DOCX">
                                        <i class="fas fa-file-word"></i>
                                    </a>
                                    @if($period->isOpen())
                                        @if($contract->status === 'active')
                                            <button type="button" class="btn btn-sm btn-light border js-edit-contract" title="Edit"
                                                    data-action="{{ route('contracts.contract.update', $contract) }}"
                                                    data-name="{{ $contract->employee_name }}"
                                                    data-position="{{ $contract->position }}"
                                                    data-rate="{{ $contract->monthly_rate }}"
                                                    data-deduction="{{ $contract->daily_deduction }}">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <form action="{{ route('contracts.contract.sign', $contract) }}" method="POST" class="d-inline-block js-confirm"
                                                  data-confirm="Mark the contract of {{ $contract->employee_name }} as signed? It can no longer be edited or removed.">
                                                @csrf
                                                <button class="btn btn-sm btn-light border" title="Mark signed"><i class="fas fa-signature"></i></button>
                                            </form>
                                        @endif
                                        @if($contract->status !== 'cancelled')
                                            <form action="{{ route('contracts.contract.cancel', $contract) }}" method="POST" class="d-inline-block js-confirm"
                                                  data-confirm="Cancel the contract of {{ $contract->employee_name }}?">
                                                @csrf
                                                <button class="btn btn-sm btn-light border" title="Cancel"><i class="fas fa-ban"></i></button>
                                            </form>
                                        @endif
                                        @if($contract->status !== 'signed')
                                            <form action="{{ route('contracts.contract.remove', $contract) }}" method="POST" class="d-inline-block js-confirm"
                                                  data-confirm="Remove {{ $contract->employee_name }} from this period?">
                                                @csrf
                                                <button class="btn btn-sm btn-light border text-danger" title="Remove"><i class="fas fa-trash"></i></button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if($period->isOpen())
{{-- Add employees --}}
<div class="modal fade contracts-modal" id="addEmployeesModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route('contracts.employees.store', $period) }}" method="POST" id="addEmployeesForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus mr-1"></i> Add Employees</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="addEmployeesErrors"></div>
                    <div class="form-group">
                        <label>Employees</label>
                        <select name="employee_ids[]" id="employeeSelect" class="form-control" multiple required style="width:100%;">
                            @foreach($employees as $employee)
                                @php $empCampus = $campuses->get($employee->camp_id); @endphp
                                <option value="{{ $employee->id }}"
                                        data-position="{{ $employee->position }}"
                                        data-meta="{{ $employee->emp_ID }} · {{ $empCampus->campus_abbr ?? 'No campus' }} · {{ $employee->position ?: 'No position' }}">
                                    {{ $employee->lname }}, {{ $employee->fname }} {{ $employee->mname ? mb_substr($employee->mname, 0, 1) . '.' : '' }} {{ $employee->suffix }} ({{ $employee->emp_ID }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Active {{ $period->typeLabel() }} employees not yet in this period. Search by name or employee ID. Everyone added in one round gets the same position and rates.</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Position</label>
                            <input type="text" name="position" id="addPosition" class="form-control" maxlength="255" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Monthly Rate (₱)</label>
                            <input type="number" name="monthly_rate" id="addMonthlyRate" class="form-control" min="0.01" step="0.01" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Daily Deduction (₱)</label>
                            <input type="number" name="daily_deduction" id="addDailyDeduction" class="form-control" min="0.01" step="0.01" required>
                            <small class="text-muted">Monthly rate ÷ {{ $daysPerMonth }}, editable.</small>
                        </div>
                    </div>
                    <small class="text-muted">Contract dates come from this period: {{ $period->start_date->format('F j, Y') }} – {{ $period->end_date->format('F j, Y') }}.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cpsu" id="addEmployeesSubmit"><i class="fas fa-plus"></i> Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Edit contract --}}
<div class="modal fade contracts-modal" id="editContractModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" id="editContractForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-pen mr-1"></i> Edit Contract</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="font-weight-bold mb-3" id="editContractName"></p>
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" name="position" class="form-control" maxlength="255" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-6 mb-0">
                            <label>Monthly Rate (₱)</label>
                            <input type="number" name="monthly_rate" class="form-control" min="0.01" step="0.01" required>
                        </div>
                        <div class="form-group col-6 mb-0">
                            <label>Daily Deduction (₱)</label>
                            <input type="number" name="daily_deduction" class="form-control" min="0.01" step="0.01" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cpsu"><i class="fas fa-save"></i> Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    var DAYS_PER_MONTH = {{ (int) $daysPerMonth }};
    var table = $('#contractsTable').DataTable({
        order: [[2, 'asc']],
        lengthChange: false,
        autoWidth: false,
        pageLength: 25,
        columnDefs: [{ orderable: false, searchable: false, targets: [0, -1] }]
    });

    // ---- Selection (works across DataTable pages)
    function checkedIds() {
        return $(table.rows().nodes()).find('.js-row-check:checked').map(function () { return this.value; }).get();
    }
    function refreshSelection() {
        var count = checkedIds().length;
        $('#selectedCount').text(count);
        $('#downloadSelectedBtn').prop('disabled', count === 0);
    }
    $('#checkAll').on('change', function () {
        $(table.rows({ search: 'applied' }).nodes()).find('.js-row-check').prop('checked', this.checked);
        refreshSelection();
    });
    $('#contractsTable').on('change', '.js-row-check', refreshSelection);
    $('#downloadSelectedForm').on('submit', function () {
        var $form = $(this);
        $form.find('input[name="contract_ids[]"]').remove();
        checkedIds().forEach(function (id) {
            $('<input>', { type: 'hidden', name: 'contract_ids[]', value: id }).appendTo($form);
        });
    });

    // ---- Confirmations
    $(document).on('submit', 'form.js-confirm', function (e) {
        var form = this;
        if (form.dataset.confirmed) return;
        e.preventDefault();
        Swal.fire({
            text: form.dataset.confirm,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, continue',
            confirmButtonColor: '#187744'
        }).then(function (result) {
            if (result.isConfirmed) {
                form.dataset.confirmed = '1';
                form.submit();
            }
        });
    });

    // ---- Edit contract
    $(document).on('click', '.js-edit-contract', function () {
        var $btn = $(this);
        var $form = $('#editContractForm');
        $form.attr('action', $btn.data('action'));
        $('#editContractName').text($btn.data('name'));
        $form.find('[name=position]').val($btn.data('position'));
        $form.find('[name=monthly_rate]').val($btn.data('rate'));
        $form.find('[name=daily_deduction]').val($btn.data('deduction'));
        $('#editContractModal').modal('show');
    });

    // ---- Add employees
    var $select = $('#employeeSelect');
    if (!$select.length) return;

    function formatEmployee(option) {
        if (!option.id) return option.text;
        var meta = $(option.element).data('meta') || '';
        return $('<span>').text(option.text).append($('<span class="emp-option-meta">').text(meta));
    }
    // Initialised when the modal first opens: the layout later runs $('.select2').select2(),
    // which would otherwise hit Select2's own container (it carries the "select2" class),
    // and a hidden modal gives Select2 no width to measure.
    $('#addEmployeesModal').one('shown.bs.modal', function () {
        $select.select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: 'Search by name or employee ID',
            dropdownParent: $('#addEmployeesModal'),
            templateResult: formatEmployee,
            closeOnSelect: false
        });
    });

    var $position = $('#addPosition');
    var $rate = $('#addMonthlyRate');
    var $deduction = $('#addDailyDeduction');
    var autoPosition = '';
    var deductionEdited = false;

    // Pre-fill position from the first selected employee unless the user typed one.
    $select.on('change', function () {
        var first = $select.find('option:selected').first();
        var suggested = first.length ? String(first.data('position') || '') : '';
        if ($position.val() === '' || $position.val() === autoPosition) {
            $position.val(suggested);
            autoPosition = suggested;
        }
    });

    $rate.on('input', function () {
        if (deductionEdited) return;
        var rate = parseFloat(this.value);
        $deduction.val(rate > 0 ? (Math.round(rate / DAYS_PER_MONTH * 100) / 100).toFixed(2) : '');
    });
    $deduction.on('input', function () { deductionEdited = this.value !== ''; });

    function showErrors(messages) {
        $('#addEmployeesErrors').removeClass('d-none').html(messages.map(function (m) { return $('<div>').text(m).html(); }).join('<br>'));
    }

    function submitEmployees(confirmOverlap) {
        var $form = $('#addEmployeesForm');
        var $btn = $('#addEmployeesSubmit').prop('disabled', true);
        var data = $form.serializeArray();
        data.push({ name: 'confirm_overlap', value: confirmOverlap ? 1 : 0 });
        $('#addEmployeesErrors').addClass('d-none').empty();

        $.ajax({ url: $form.attr('action'), method: 'POST', data: $.param(data), dataType: 'json' })
            .done(function () { window.location.reload(); })
            .fail(function (xhr) {
                $btn.prop('disabled', false);
                var res = xhr.responseJSON || {};
                if (xhr.status === 409 && res.needs_confirmation) {
                    var list = $('<ul class="text-left mb-0">');
                    res.conflicts.forEach(function (c) { list.append($('<li>').text(c)); });
                    Swal.fire({
                        title: 'Overlapping contracts',
                        html: $('<div>').append($('<p>').text('These employees already have a contract in another period that overlaps these dates:')).append(list).html(),
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Add anyway',
                        confirmButtonColor: '#187744'
                    }).then(function (result) {
                        if (result.isConfirmed) submitEmployees(true);
                    });
                    return;
                }
                if (res.errors) {
                    showErrors(Object.values(res.errors).reduce(function (all, list) { return all.concat(list); }, []));
                } else {
                    showErrors([res.message || 'Something went wrong. Please try again.']);
                }
            });
    }

    $('#addEmployeesForm').on('submit', function (e) {
        e.preventDefault();
        submitEmployees(false);
    });
});
</script>
@endsection
