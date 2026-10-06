@extends('layouts.master')

@section('body')
<style>
    .contracts-page .card { border:1px solid var(--cpsu-line, #e4ebe7); border-radius:var(--cpsu-radius, 12px); box-shadow:none; }
    .contracts-head { align-items:center; display:flex; flex-wrap:wrap; gap:12px; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--cpsu-line, #e4ebe7); }
    .contracts-head h1 { color:var(--cpsu-ink-900, #1c2b24); font-size:1.15rem; font-weight:700; margin:0; }
    .contracts-filters { align-items:center; display:flex; flex-wrap:wrap; gap:6px; }
    .contracts-filters select.form-control { flex:0 0 auto; width:auto; min-width:120px; }
    .contracts-page .table td, .contracts-page .table th { vertical-align:middle; }
    .contracts-page .btn-cpsu, .contracts-modal .btn-cpsu { background:#187744; border-color:#187744; color:#fff; }
    .contracts-page .btn-cpsu:hover, .contracts-modal .btn-cpsu:hover { background:#146a3b; border-color:#146a3b; color:#fff; }
    .contract-badge { border-radius:999px; display:inline-block; font-size:.75rem; font-weight:600; padding:3px 10px; }
    .contract-badge-open { background:#e3f2e9; color:#146a3b; }
    .contract-badge-closed { background:#eceff1; color:#56655d; }
    .contracts-modal .modal-header { background:#187744; color:#fff; }
    .contracts-modal .modal-header .close { color:#fff; opacity:1; }
</style>

<div class="container-fluid contracts-page">
    <div class="card">
        <div class="contracts-head">
            <h1><i class="fas fa-file-signature mr-1"></i> Contract of Services</h1>
            <button class="btn btn-cpsu" data-toggle="modal" data-target="#newPeriodModal">
                <i class="fas fa-plus"></i> New Contract Period
            </button>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('contracts.index') }}" class="contracts-filters" id="periodFilters">
                <select name="type" class="form-control form-control-sm">
                    <option value="">All types</option>
                    @foreach($types as $key => $type)
                        <option value="{{ $key }}" {{ ($filters['type'] ?? '') === $key ? 'selected' : '' }}>{{ $type['label'] }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-control form-control-sm">
                    <option value="">All statuses</option>
                    <option value="open" {{ ($filters['status'] ?? '') === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="closed" {{ ($filters['status'] ?? '') === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
                <select name="year" class="form-control form-control-sm">
                    <option value="">All years</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ (string) ($filters['year'] ?? '') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
                @if(array_filter($filters))
                    <a href="{{ route('contracts.index') }}" class="btn btn-sm btn-light border">Clear</a>
                @endif
            </form>

            <div class="table-responsive">
                <table id="periodsTable" class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Start</th>
                            <th>End</th>
                            <th class="text-center">Employees</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($periods as $period)
                            <tr>
                                <td><a href="{{ route('contracts.show', $period) }}" class="font-weight-bold text-dark">{{ $period->title }}</a></td>
                                <td>{{ $period->typeLabel() }}</td>
                                <td data-order="{{ $period->start_date->format('Y-m-d') }}">{{ $period->start_date->format('M j, Y') }}</td>
                                <td data-order="{{ $period->end_date->format('Y-m-d') }}">{{ $period->end_date->format('M j, Y') }}</td>
                                <td class="text-center">{{ $period->employees_count }}</td>
                                <td class="text-center">
                                    <span class="contract-badge contract-badge-{{ $period->status }}">{{ ucfirst($period->status) }}</span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ route('contracts.show', $period) }}" class="btn btn-sm btn-cpsu" title="Open">
                                        <i class="fas fa-folder-open"></i>
                                    </a>
                                    @if($period->isOpen() && $period->signed_count == 0)
                                        <button type="button" class="btn btn-sm btn-light border js-edit-period" title="Edit dates"
                                                data-action="{{ route('contracts.update', $period) }}"
                                                data-title="{{ $period->title }}"
                                                data-start="{{ $period->start_date->format('Y-m-d') }}"
                                                data-end="{{ $period->end_date->format('Y-m-d') }}">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                    @endif
                                    @if($period->isOpen())
                                        <form action="{{ route('contracts.close', $period) }}" method="POST" class="d-inline-block js-confirm"
                                              data-confirm="Close &quot;{{ $period->title }}&quot;? Contracts can still be downloaded, but nothing can be edited.">
                                            @csrf
                                            <button class="btn btn-sm btn-light border" title="Close period"><i class="fas fa-lock"></i></button>
                                        </form>
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

{{-- New period --}}
<div class="modal fade contracts-modal" id="newPeriodModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('contracts.store') }}" method="POST" id="newPeriodForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus mr-1"></i> New Contract Period</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Contract Type</label>
                        <select name="contract_type" class="form-control" required>
                            @foreach($types as $key => $type)
                                <option value="{{ $key }}" data-label="{{ $type['label'] }}" {{ empty($type['enabled']) ? 'disabled' : '' }} {{ old('contract_type', 'job_order') === $key ? 'selected' : '' }}>
                                    {{ $type['label'] }}{{ empty($type['enabled']) ? ' (Coming soon)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
                        </div>
                        <div class="form-group col-6">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Title <small class="text-muted">(optional)</small></label>
                        <input type="text" name="title" class="form-control" maxlength="255" value="{{ old('title') }}" placeholder="Job Order Contract: January 1, 2026 – April 1, 2026">
                        <small class="text-muted">Suggested from the type and dates; you can change it.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cpsu"><i class="fas fa-save"></i> Create Period</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Edit period --}}
<div class="modal fade contracts-modal" id="editPeriodModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" id="editPeriodForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-pen mr-1"></i> Edit Contract Period</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="form-group col-6">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" maxlength="255">
                        <small class="text-muted">Leave blank to use the suggested title.</small>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    $('#periodsTable').DataTable({
        order: [],
        lengthChange: false,
        autoWidth: false,
        columnDefs: [{ orderable: false, targets: -1 }],
        // Filters share the toolbar row with the search box.
        dom: "<'row align-items-center mb-2'<'col-md-8 period-filters-slot'><'col-md-4'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row'<'col-md-5'i><'col-md-7'p>>"
    });
    $('#periodFilters').appendTo('.period-filters-slot');

    $('#periodFilters select').on('change', function () { this.form.submit(); });

    function longDate(value) {
        if (!value) return '';
        var parts = value.split('-');
        var date = new Date(parts[0], parts[1] - 1, parts[2]);
        return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    }

    // Suggest a title until the user types their own.
    var $newForm = $('#newPeriodForm');
    var $title = $newForm.find('[name=title]');
    var lastSuggestion = '';
    function suggestTitle() {
        var label = $newForm.find('[name=contract_type] option:selected').data('label') || 'Contract';
        var start = $newForm.find('[name=start_date]').val();
        var end = $newForm.find('[name=end_date]').val();
        if (!start || !end) return;
        var suggestion = label + ' Contract: ' + longDate(start) + ' – ' + longDate(end);
        if ($title.val() === '' || $title.val() === lastSuggestion) {
            $title.val(suggestion);
        }
        lastSuggestion = suggestion;
    }
    $newForm.on('change', '[name=contract_type], [name=start_date], [name=end_date]', suggestTitle);
    $newForm.on('change', '[name=start_date]', function () {
        $newForm.find('[name=end_date]').attr('min', this.value);
    });

    $(document).on('click', '.js-edit-period', function () {
        var $btn = $(this);
        var $form = $('#editPeriodForm');
        $form.attr('action', $btn.data('action'));
        $form.find('[name=start_date]').val($btn.data('start'));
        $form.find('[name=end_date]').val($btn.data('end')).attr('min', $btn.data('start'));
        $form.find('[name=title]').val($btn.data('title'));
        $('#editPeriodModal').modal('show');
    });

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

    @if($errors->any() && old('contract_type'))
        $('#newPeriodModal').modal('show');
    @endif
});
</script>
@endsection
