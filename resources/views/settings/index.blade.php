@extends('layouts.master')

@section('body')
<style>
    .custom-label {
        width: 45px;
        padding: 0px;
        padding-left: 5px;
        text-align: center;
    }
    .settings-group {
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        background: #fff;
    }
    .group-header {
        margin: -1.25rem -1.25rem 1.25rem -1.25rem;
        padding: 0.75rem 1.25rem;
        background: #f8f9fa;
        border-bottom: 1px solid #e0e0e0;
        border-radius: 6px 6px 0 0;
        font-weight: 600;
        color: #2c3e50;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h2 class="card-title text-success1"><b>SYSTEM SETTINGS</b></h2>
                </div>

                <div class="card-body bg-form">
                    @php
                        $positionFields = [
                            'suc_pres' => ['SUC President', true],
                            'vpaa' => ['Vice President of Academic Affairs', false],
                            'vpaf' => ['Vice President of Administration and Finance', false],
                            'hr' => ['HR Head', true],
                        ];
                        $selectedKiosk = old('hr_kiosk', $kioskAccess);
                        $selectedDtr = array_map('intval', old('dtr_acct', $dtrFullAccess));
                    @endphp

                    <form id="systemSettingsForm" method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        @method('PATCH')

                        <!-- Group 1: Executive / Leadership Positions -->
                        <div class="settings-group">
                            <div class="group-header">Executive / Leadership Positions</div>
                            <div class="row">
                                @foreach($positionFields as $field => [$label, $required])
                                    <div class="col-md-6 col-lg-3">
                                        <div class="mb-3">
                                            <label class="d-block font-weight-bold" for="{{ $field }}">
                                                {{ $label }} @if($required)<span class="text-danger">*</span>@endif
                                            </label>
                                            <select id="{{ $field }}" name="{{ $field }}" class="form-control form-select select2 @error($field) is-invalid @enderror" style="width: 100%;" {{ $required ? 'required' : '' }}>
                                                <option value="">-- Select employee --</option>
                                                @foreach($employees as $emp)
                                                    <option value="{{ $emp->id }}" {{ (int) old($field, $settings->$field) === (int) $emp->id ? 'selected' : '' }}>
                                                        {{ ucfirst($emp->fname) }} {{ ucfirst($emp->lname) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error($field)<small class="text-danger">{{ $message }}</small>@enderror
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <small class="form-text text-muted">
                                Changing the SUC President or HR Head moves leave applications still waiting on their signature to the new person. Applications they already signed keep the original signatory.
                            </small>
                        </div>

                        <!-- Group 2: Time & Attendance Settings -->
                        <div class="settings-group">
                            <div class="group-header">Time & Attendance Settings</div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="d-block font-weight-bold" for="te_rstrct_lvl">Time Entry Restriction</label>
                                        <select id="te_rstrct_lvl" name="te_rstrct_lvl" class="form-control form-select select2" style="width: 100%;">
                                            @foreach([0 => 'None', 1 => 'Partial Restriction', 2 => 'Full Restriction'] as $value => $label)
                                                <option value="{{ $value }}" {{ (int) old('te_rstrct_lvl', $settings->te_rstrct_lvl ?? 2) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('te_rstrct_lvl')<small class="text-danger">{{ $message }}</small>@enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="d-block font-weight-bold mb-2" for="sync_backups">HR Kiosk Backtrack Sync</label>
                                        <input type="hidden" name="sync_backups" value="0">
                                        <input type="checkbox" id="sync_backups" name="sync_backups" value="1" data-bootstrap-switch
                                               data-off-color="danger" data-on-color="success"
                                               {{ old('sync_backups', $settings->sync_backups) ? 'checked' : '' }}>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="d-block font-weight-bold" for="hr_kiosk">HR Kiosk Access</label>
                                        <select id="hr_kiosk" name="hr_kiosk[]" class="form-control form-select select2" style="width: 100%;" multiple>
                                            @foreach($employees as $emp)
                                                <option value="{{ $emp->emp_ID }}" {{ in_array($emp->emp_ID, $selectedKiosk, true) ? 'selected' : '' }}>
                                                    {{ ucfirst($emp->fname) }} {{ ucfirst($emp->lname) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('hr_kiosk.*')<small class="text-danger">{{ $message }}</small>@enderror
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="d-block font-weight-bold" for="dtr_acct">DTR Full Access</label>
                                        <select id="dtr_acct" name="dtr_acct[]" class="form-control form-select select2" style="width: 100%;" multiple>
                                            @foreach($employees as $emp)
                                                <option value="{{ $emp->id }}" {{ in_array((int) $emp->id, $selectedDtr, true) ? 'selected' : '' }}>
                                                    {{ ucfirst($emp->fname) }} {{ ucfirst($emp->lname) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('dtr_acct.*')<small class="text-danger">{{ $message }}</small>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Group 3: Email & Notification Settings -->
                        <div class="settings-group">
                            <div class="group-header">Email & Notification Settings</div>
                            <div class="row">
                                @foreach(['records_office_email' => 'Records Office Email', 'job_portal_email' => 'Job Portal Email'] as $field => $label)
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="d-block font-weight-bold" for="{{ $field }}">{{ $label }}</label>
                                            <input type="email" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $settings->$field) }}"
                                                   class="form-control form-control-sm @error($field) is-invalid @enderror" placeholder="Enter email">
                                            @error($field)<small class="text-danger">{{ $message }}</small>@enderror
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="text-right mb-4">
                            <button type="submit" class="btn btn-success" id="saveSettingsBtn">
                                <i class="fas fa-save mr-1"></i> Save Settings
                            </button>
                        </div>
                    </form>

                    <!-- Group 4: Maintenance -->
                    <div class="settings-group">
                        <div class="group-header">Maintenance</div>
                        <form id="maintenanceSettingsForm" method="POST" action="{{ route('settings.maintenance.update') }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="maintenance" value="0">

                            <div class="row align-items-center">
                                <div class="col-12">
                                    <div class="mb-0">
                                        <label class="d-block font-weight-bold mb-2" for="maintenanceSwitch">System Maintenance Mode</label>
                                        <input type="checkbox" id="maintenanceSwitch" name="maintenance" value="1"
                                               data-bootstrap-switch data-off-color="danger" data-on-color="success"
                                               {{ $settings->maintenance ? 'checked' : '' }}>
                                        <small class="form-text text-muted mt-2">
                                            Changes save automatically. When enabled, the login form and Google sign-in are replaced by the Under Maintenance page for everyone, including administrators. Keep this session open so you can turn maintenance mode off again.
                                        </small>
                                        <small id="maintenanceSaving" class="form-text text-success mt-1 d-none">
                                            <i class="fas fa-spinner fa-spin mr-1"></i> Saving maintenance setting...
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        if (!window.jQuery || !jQuery.fn.bootstrapSwitch) {
            return;
        }

        jQuery('#systemSettingsForm').on('submit', function () {
            jQuery('#saveSettingsBtn').prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');
        });

        const maintenanceSwitch = jQuery('#maintenanceSwitch');
        let isSubmitting = false;

        maintenanceSwitch.on('switchChange.bootstrapSwitch', function () {
            if (isSubmitting) {
                return;
            }

            isSubmitting = true;
            document.getElementById('maintenanceSaving').classList.remove('d-none');
            document.getElementById('maintenanceSettingsForm').submit();
        });
    });
</script>
@endsection
