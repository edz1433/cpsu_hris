@extends('layouts.master')

@section('body')
<style>
    .audit-page .audit-filters-card .dash-card-header { padding: 9px 14px; }
    .audit-page .audit-filters-card .dash-card-body { padding: 12px 14px; }
    .audit-page .audit-filter-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)) auto; gap: 10px; align-items: end; }
    .audit-page .audit-field { min-width: 0; }
    .audit-page .audit-field label { display: block; margin-bottom: 3px; font-size: 12px; font-weight: 600; color: #344054; }
    .audit-page .audit-field select.form-control { height: 34px; border-radius: 6px; cursor: pointer; background-color: #fff; }
    .audit-page .audit-filter-actions { display: flex; gap: 6px; }
    .audit-page .audit-filter-actions .btn { height: 34px; padding: 5px 10px; display: inline-flex; align-items: center; justify-content: center; gap: 5px; white-space: nowrap; }
    .audit-page .audit-page-size { display: flex; align-items: center; gap: 7px; color: #667085; font-size: 12px; }
    .audit-page .audit-page-size .select2-container { min-width: 82px; }
    .audit-page .audit-filters-card .select2-container--bootstrap4 .select2-selection--single { height: 34px; min-height: 34px; border-radius: 6px; }
    .audit-page .audit-filters-card .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered { line-height: 32px; font-size: 13px; }
    .audit-page .audit-filters-card .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow { top: 0; height: 100%; }
    .audit-page .audit-filters-card .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow b { top: 50%; }
    .audit-page .audit-filters-card .select2-container--bootstrap4.select2-container--focus .select2-selection { border-color: var(--cpsu-green-600); box-shadow: 0 0 0 3px rgba(28, 112, 59, .12); }
    .audit-page .audit-table td { vertical-align: middle; }
    .audit-page .audit-table th { white-space: nowrap; }
    .audit-page .audit-summary { min-width: 260px; max-width: 420px; white-space: normal; }
    .audit-page .audit-meta { color: #667085; font-size: 12px; }
    .audit-page .audit-badge { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #eef2f6; font-size: 11px; font-weight: 700; letter-spacing: .02em; text-transform: uppercase; }
    .audit-page .audit-badge.success { background: #e6f6ea; color: #176333; }
    .audit-page .audit-badge.failure, .audit-page .audit-badge.rejected, .audit-page .audit-badge.error { background: #fff0ee; color: #a3291f; }
    .audit-page .audit-badge.unchanged { background: #fff6dd; color: #835d00; }
    .audit-page .audit-json { max-height: 55vh; overflow: auto; padding: 16px; margin: 0; border: 1px solid #e2e8f0; border-radius: 7px; background: #f7f9fb; color: #243047; white-space: pre-wrap; overflow-wrap: anywhere; font-size: 12px; }
    @media (max-width: 1250px) { .audit-page .audit-filter-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 700px) { .audit-page .audit-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 480px) { .audit-page .audit-filter-grid { grid-template-columns: 1fr; } }
</style>

<div class="container-fluid dash audit-page">
    <div class="dtr-head">
        <div>
            <h1>Audit Logs</h1>
            <p>Review recorded activity, attendance attempts, and errors.</p>
        </div>
        <span class="audit-meta">{{ $logs->count() }} {{ \Illuminate\Support\Str::plural('entry', $logs->count()) }} loaded</span>
    </div>

    <div class="dash-card audit-filters-card">
        <div class="dash-card-header">
            <h5><i class="fas fa-filter" style="color: var(--cpsu-green-600);"></i> Filter Logs</h5>
            <div class="audit-page-size">
                <label class="mb-0" for="audit-per-page">Rows</label>
                <select id="audit-per-page" name="per_page" form="audit-filter-form" class="form-control audit-select" onchange="this.form.submit()">
                    @foreach([25, 50, 100] as $count)
                        <option value="{{ $count }}" {{ (int) ($filters['per_page'] ?? 25) === $count ? 'selected' : '' }}>{{ $count }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="dash-card-body">
            @if($errors->any())
                <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
            @endif
            <form id="audit-filter-form" action="{{ route('audit-logs.index') }}" method="GET">
                @foreach($filters as $key => $value)
                    @if(str_starts_with($key, 'registration_') && $key !== 'registration_page')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <div class="audit-filter-grid">
                    <div class="audit-field">
                        <label for="audit-actor">Performed By</label>
                        <select id="audit-actor" name="actor" class="form-control audit-select audit-remote" data-type="actor">
                            <option value="">All users</option>
                            @if(($filters['actor'] ?? '') === '__system__')
                                <option value="__system__" selected>System / unknown</option>
                            @elseif(!empty($filters['actor']))
                                <option value="{{ $filters['actor'] }}" selected>{{ $filters['actor'] }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="audit-field">
                        <label for="audit-person">Affected Employee</label>
                        <select id="audit-person" name="person" class="form-control audit-select audit-remote" data-type="person">
                            <option value="">All employees</option>
                            @if(!empty($filters['person']))
                                <option value="{{ $filters['person'] }}" selected>{{ $filters['person'] }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="audit-field">
                        <label for="audit-kiosk">HR Kiosk</label>
                        <select id="audit-kiosk" name="kiosk_id" class="form-control audit-select audit-remote" data-type="kiosk">
                            <option value="">All HR Kiosks</option>
                            @if(isset($filters['kiosk_id']) && $filters['kiosk_id'] !== '')
                                <option value="{{ $filters['kiosk_id'] }}" selected>{{ $kioskLabels->get($filters['kiosk_id'], 'HR Kiosk (name unavailable)') }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="audit-field">
                        <label for="audit-campus">Campus</label>
                        <select id="audit-campus" name="campus_id" class="form-control audit-select">
                            <option value="">All campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus->id }}" {{ (string) ($filters['campus_id'] ?? '') === (string) $campus->id ? 'selected' : '' }}>{{ $campus->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="audit-field">
                        <label for="audit-office">Office</label>
                        <select id="audit-office" name="office_id" class="form-control audit-select">
                            <option value="">All offices</option>
                            @foreach($offices as $office)
                                <option value="{{ $office->id }}" {{ (string) ($filters['office_id'] ?? '') === (string) $office->id ? 'selected' : '' }}>{{ $office->office_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="audit-field">
                        <label for="audit-module">Module</label>
                        <select id="audit-module" name="module" class="form-control audit-select">
                            <option value="">All modules</option>
                            @foreach($modules as $module)
                                <option value="{{ $module }}" {{ ($filters['module'] ?? '') === $module ? 'selected' : '' }}>{{ ['dtr' => 'DTR', 'pds' => 'PDS', 'spms' => 'SPMS'][$module] ?? \Illuminate\Support\Str::headline($module) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="audit-field">
                        <label for="audit-date-range">Date Range</label>
                        <select id="audit-date-range" name="date_range" class="form-control audit-select" onchange="if (this.value) { document.getElementById('audit-date-from').value = ''; document.getElementById('audit-date-to').value = ''; }">
                            <option value="">All dates / custom</option>
                            @foreach(['today' => 'Today', 'yesterday' => 'Yesterday', 'last_7_days' => 'Last 7 days', 'last_30_days' => 'Last 30 days', 'this_month' => 'This month', 'last_month' => 'Last month'] as $range => $label)
                                <option value="{{ $range }}" {{ ($filters['date_range'] ?? '') === $range ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="audit-field">
                        <label for="audit-date-from">From date</label>
                        <input id="audit-date-from" name="date_from" type="date" class="form-control" value="{{ $filters['date_from'] ?? '' }}" onchange="auditUseCustomDates()">
                    </div>
                    <div class="audit-field">
                        <label for="audit-date-to">To date</label>
                        <input id="audit-date-to" name="date_to" type="date" class="form-control" value="{{ $filters['date_to'] ?? '' }}" onchange="auditUseCustomDates()">
                    </div>
                    <div class="audit-field audit-filter-actions">
                        <button type="submit" class="btn btn-success"><i class="fas fa-search"></i> Apply</button>
                        <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(isset($filters['kiosk_id']) && $filters['kiosk_id'] !== '')
        <div class="dash-card">
            <div class="dash-card-header"><h5>HR Kiosk Upload History</h5></div>
            <div class="dash-card-body">
                <strong>{{ $kioskLabels->get($filters['kiosk_id'], 'HR Kiosk (name unavailable)') }}</strong>
                @if($latestKioskUpload)
                    <p class="mb-1">Latest recorded upload event:
                        {{ \Carbon\Carbon::parse($latestKioskUpload->occurred_at)->format('M j, Y · g:i:s A') }} (Manila)
                        <span class="audit-badge {{ $latestKioskUpload->outcome }}">{{ $latestKioskUpload->outcome }}</span>
                        · {{ str_replace('_', ' ', $latestKioskUpload->action) }}
                        @if($latestKioskUpload->reason_code)
                            · {{ str_replace('_', ' ', $latestKioskUpload->reason_code) }}
                        @endif
                    </p>
                @else
                    <p class="mb-1">No recorded uploads for this HR Kiosk.</p>
                @endif
                <div class="audit-meta">Across all dates and employees. This shows uploads containing this device's punches; it does not establish connection or disconnection.</div>
            </div>
        </div>
    @endif

    <div class="dash-card">
        <div class="dash-card-header">
            <h5><i class="far fa-clock" style="color: var(--cpsu-green-600);"></i> Recent Activity</h5>
            <span class="audit-meta">Dates use attendance time when available, otherwise server event time.</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover audit-table mb-0">
                <thead>
                    <tr><th>#</th><th>Attendance / Event Time <span class="audit-meta">(Manila)</span></th><th>Action</th><th>Outcome</th><th>Performed By</th><th>Target</th><th>Kiosk / Zone</th><th>Activity</th><th>Details</th></tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php
                            $details = json_decode($log->details ?: '{}', true);
                            if (!is_array($details)) $details = [];
                            // Older rows stored this text themselves.
                            if (($details['source'] ?? null) === 'desktop_sync' && isset($details['upload_counts'])) {
                                $details['count_basis'] ??= \App\Services\Audit\AuditDtrSyncRecorder::COUNT_BASIS;
                            }
                            $displayModule = $log->module === 'dtr' && in_array($log->route_name, ['readTiredness', 'tirednessSearch', 'pdfTirednes'], true)
                                ? 'tardiness' : $log->module;
                            $details = array_merge($details, [
                                'reason_code' => $log->reason_code,
                                'ip' => $log->ip_address,
                                'user_agent' => $log->user_agent,
                                'route_name' => $log->route_name,
                                'http_method' => $log->http_method,
                                'request_id' => $log->request_id,
                            ]);
                        @endphp
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td class="text-nowrap">
                                {{ \Carbon\Carbon::parse($details['attendance_at'] ?? $log->occurred_at)->format('M j, Y · g:i:s A') }}
                                @if(isset($details['attendance_at']))
                                    <div class="audit-meta">{{ ($details['source'] ?? '') === 'desktop_sync' ? 'Kiosk-reported time' : 'Attendance time' }}</div>
                                    <div class="audit-meta">Server event: {{ \Carbon\Carbon::parse($log->occurred_at)->format('M j, Y · g:i:s A') }}</div>
                                @endif
                            </td>
                            <td><span class="audit-badge">{{ \App\Services\Audit\AuditFormatter::actionLabel($log) }}</span><div class="audit-meta">{{ ['dtr' => 'DTR', 'pds' => 'PDS', 'spms' => 'SPMS'][$displayModule] ?? \Illuminate\Support\Str::headline($displayModule) }}</div></td>
                            <td><span class="audit-badge {{ in_array($log->outcome, ['success', 'failure', 'rejected', 'unchanged', 'error']) ? $log->outcome : '' }}">{{ $log->outcome }}</span></td>
                            <td>{{ $log->actor_name ?: ucfirst($log->actor_type ?: 'Unknown') }}<div class="audit-meta">{{ $log->ip_address ?: 'No IP' }}</div></td>
                            <td>{{ $log->target_name ?: ($log->employee_code ?: ($log->target_id ?: '—')) }}<div class="audit-meta">{{ $log->target_type }}</div></td>
                            <td>
                                @if($log->module === 'dtr' && ($details['source'] ?? null) === 'desktop_sync')
                                    @php
                                        $submittedKiosks = collect(is_array($details['submitted_device_ids'] ?? null)
                                            ? $details['submitted_device_ids'] : [$details['submitted_device_id'] ?? null])
                                            ->filter(fn ($id) => is_scalar($id) && preg_match('/^-?\d{1,10}$/', (string) $id))
                                            ->map(fn ($id) => (string) $id)->unique()->values()->all();
                                    @endphp
                                    @if($submittedKiosks)
                                        {{ collect($submittedKiosks)->map(fn ($id) => $kioskLabels->get($id, 'HR Kiosk (name unavailable)'))->implode(', ') }}
                                    @else
                                        <span class="audit-meta">Device not recorded</span>
                                    @endif
                                    @if(isset($details['device_id']) && (count($submittedKiosks) > 1 || !in_array((string) $details['device_id'], $submittedKiosks, true)))
                                        <div class="audit-meta">Stored punch: {{ $kioskLabels->get((string) $details['device_id'], 'HR Kiosk (name unavailable)') }}</div>
                                    @endif
                                @elseif($log->module === 'dtr' && isset($details['zone_id']) && is_scalar($details['zone_id']) && preg_match('/^-?\d{1,10}$/', (string) $details['zone_id']))
                                    {{ $timeEntryLabels->get((string) (int) $details['zone_id'], 'Time Entry zone (name unavailable)') }}
                                    <div class="audit-meta">Time Entry</div>
                                @else
                                    <span class="audit-meta">—</span>
                                @endif
                            </td>
                            <td class="audit-summary">{{ \App\Services\Audit\AuditFormatter::describe($log) }}</td>
                            <td><button type="button" class="btn btn-sm btn-outline-secondary audit-details-button" data-toggle="modal" data-target="#auditDetailsModal" data-audit-json="{{ json_encode(\Illuminate\Support\Arr::except($details, ['submitted_device_id', 'submitted_device_ids', 'device_id', 'zone_id']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}" data-audit-id="{{ $log->id }}"><i class="fas fa-info-circle"></i> Details</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-5">No audit entries match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="dash-card-body d-flex justify-content-end pb-1">{{ $logs->links('pagination::simple-bootstrap-4') }}</div>
        @endif
    </div>
    @include('audit-logs.registrations')
</div>

<div class="modal fade" id="auditDetailsModal" tabindex="-1" role="dialog" aria-labelledby="auditDetailsTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="auditDetailsTitle">Audit Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body"><pre class="audit-json" id="auditDetailsJson"></pre></div>
        </div>
    </div>
</div>

<script>
function auditUseCustomDates() {
    var preset = document.getElementById('audit-date-range');
    preset.value = '';
    if (window.jQuery && jQuery.fn.select2) jQuery(preset).trigger('change.select2');
}
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !jQuery.fn.select2) return;
    var options = {
        theme: 'bootstrap4',
        width: '100%',
        minimumResultsForSearch: 0,
        tags: false
    };
    jQuery('.audit-filter-grid .audit-select').not('.audit-remote').select2(options);
    jQuery('.audit-remote').each(function () {
        var field = jQuery(this);
        field.select2(Object.assign({}, options, {
            placeholder: field.find('option:first').text(),
            allowClear: true,
            minimumInputLength: field.data('type') === 'person' ? 2 : 0,
            ajax: {
                url: @json(route('audit-logs.options')),
                dataType: 'json',
                delay: 250,
                cache: true,
                data: function (params) {
                    return { type: field.data('type'), q: params.term || '' };
                },
                processResults: function (data) {
                    return { results: data.results || [] };
                }
            }
        }));
    });
    jQuery('#audit-per-page').select2(Object.assign({}, options, { width: '82px' }));
});

document.addEventListener('click', function (event) {
    var button = event.target.closest('.audit-details-button');
    if (!button) return;
    var raw = button.getAttribute('data-audit-json');
    var output = document.getElementById('auditDetailsJson');
    document.getElementById('auditDetailsTitle').textContent = 'Audit Details #' + button.getAttribute('data-audit-id');
    try {
        output.textContent = raw ? JSON.stringify(JSON.parse(raw), null, 2) : '{}';
    } catch (e) {
        output.textContent = raw || '{}';
    }
});
</script>
@endsection
