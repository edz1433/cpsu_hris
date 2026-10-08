@extends('layouts.master')

@section('body')
@php
    $positionFields = [
        'suc_pres' => ['SUC President', true],
        'vpaa' => ['Vice President of Academic Affairs', false],
        'vpaf' => ['Vice President of Administration and Finance', false],
        'hr' => ['HR Head', true],
    ];
    $selectedKiosk = old('hr_kiosk', $kioskAccess);
    $selectedDtr = array_map('intval', old('dtr_acct', $dtrFullAccess));
    // [label, what it does on the HR kiosk]
    $restrictionLevels = [
        0 => ['None', 'Time entries are allowed at any time.'],
        1 => ['Partial', 'Time entries are allowed only from 11:00 AM to 1:30 PM.'],
        2 => ['Full', 'Time entries are not available.'],
    ];
    $currentLevel = (int) old('te_rstrct_lvl', $settings->te_rstrct_lvl ?? 2);
    $isMaintenance = (bool) $settings->maintenance;
@endphp
<div class="container-fluid dash">
    <div class="dtr-head">
        <div>
            <h1>Settings</h1>
            <p>System-wide signatories, time and attendance rules, notification emails and maintenance mode.</p>
        </div>
        <span class="lv-pill {{ $isMaintenance ? 'is-deducted' : 'is-added' }} set-state">
            <i class="fas fa-circle"></i> {{ $isMaintenance ? 'Maintenance mode is on' : 'System is live' }}
        </span>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <form id="systemSettingsForm" method="POST" action="{{ route('settings.update') }}" class="dtr-form">
                @csrf
                @method('PATCH')

                {{-- Signatories --}}
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h5><i class="fas fa-user-tie" style="color: var(--cpsu-green-600);"></i>Executive / leadership positions</h5>
                    </div>
                    <div class="dash-card-body">
                        <div class="form-row">
                            @foreach($positionFields as $field => [$label, $required])
                                <div class="col-md-6 dtr-field">
                                    <label class="dtr-label" for="{{ $field }}">
                                        {{ $label }} @if($required)<span class="text-danger">*</span>@else<span class="font-weight-normal">(optional)</span>@endif
                                    </label>
                                    <select id="{{ $field }}" name="{{ $field }}" class="form-control select2 @error($field) is-invalid @enderror" style="width: 100%;" {{ $required ? 'required' : '' }}>
                                        <option value="">-- Select employee --</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}" {{ (int) old($field, $settings->$field) === (int) $emp->id ? 'selected' : '' }}>
                                                {{ ucfirst($emp->fname) }} {{ ucfirst($emp->lname) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error($field)<small class="text-danger">{{ $message }}</small>@enderror
                                </div>
                            @endforeach
                        </div>
                        <div class="ete-note mb-0">
                            <i class="fas fa-info-circle"></i>
                            <span>Changing the SUC President or HR Head moves leave applications still waiting on their signature to the new person. Applications they already signed keep the original signatory.</span>
                        </div>
                    </div>
                </div>

                {{-- Time & attendance --}}
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h5><i class="fas fa-clock" style="color: var(--cpsu-green-600);"></i>Time and attendance</h5>
                    </div>
                    <div class="dash-card-body">
                        <div class="set-row">
                            <div class="set-row-text">
                                <span class="dtr-label mb-1" id="te_rstrct_lvl_label">Time entry restriction</span>
                                <p class="set-help" id="teRestrictionHelp">{{ $restrictionLevels[$currentLevel][1] ?? '' }}</p>
                            </div>
                            <div class="dtr-seg set-seg" role="radiogroup" aria-labelledby="te_rstrct_lvl_label">
                                @foreach($restrictionLevels as $value => [$label, $help])
                                    <input type="radio" name="te_rstrct_lvl" id="te_rstrct_lvl{{ $value }}" value="{{ $value }}" data-help="{{ $help }}" {{ $currentLevel === $value ? 'checked' : '' }} required>
                                    <label for="te_rstrct_lvl{{ $value }}">{{ $label }}</label>
                                @endforeach
                            </div>
                        </div>
                        @error('te_rstrct_lvl')<small class="text-danger d-block">{{ $message }}</small>@enderror

                        <div class="set-row">
                            <div class="set-row-text">
                                <label class="dtr-label mb-0" for="sync_backups">HR kiosk backtrack sync</label>
                            </div>
                            <input type="hidden" name="sync_backups" value="0">
                            <label class="set-switch">
                                <input type="checkbox" id="sync_backups" name="sync_backups" value="1" {{ old('sync_backups', $settings->sync_backups) ? 'checked' : '' }}>
                                <span class="set-switch-track" aria-hidden="true"></span>
                                <span class="set-switch-text" data-on="On" data-off="Off"></span>
                            </label>
                        </div>

                        <div class="dtr-field mt-3">
                            <label class="dtr-label" for="hr_kiosk">HR kiosk access <span class="font-weight-normal set-count" data-count-for="hr_kiosk"></span></label>
                            <select id="hr_kiosk" name="hr_kiosk[]" class="form-control select2" style="width: 100%;" multiple>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->emp_ID }}" {{ in_array($emp->emp_ID, $selectedKiosk, true) ? 'selected' : '' }}>
                                        {{ ucfirst($emp->fname) }} {{ ucfirst($emp->lname) }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="set-help mt-1">Employees who can sign in to the HR kiosk's admin mode.</p>
                            @error('hr_kiosk.*')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>

                        <div class="dtr-field mb-0">
                            <label class="dtr-label" for="dtr_acct">DTR full access <span class="font-weight-normal set-count" data-count-for="dtr_acct"></span></label>
                            <select id="dtr_acct" name="dtr_acct[]" class="form-control select2" style="width: 100%;" multiple>
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

                {{-- Email --}}
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h5><i class="fas fa-envelope" style="color: var(--cpsu-green-600);"></i>Email and notifications</h5>
                    </div>
                    <div class="dash-card-body">
                        <div class="form-row">
                            @foreach(['records_office_email' => 'Records office email', 'job_portal_email' => 'Job portal email'] as $field => $label)
                                <div class="col-md-6 dtr-field mb-md-0">
                                    <label class="dtr-label" for="{{ $field }}">{{ $label }} <span class="font-weight-normal">(optional)</span></label>
                                    <input type="email" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $settings->$field) }}"
                                           class="form-control @error($field) is-invalid @enderror" placeholder="name@cpsu.edu.ph" autocomplete="off">
                                    @error($field)<small class="text-danger">{{ $message }}</small>@enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="set-savebar">
                    <span class="set-help mb-0">Saves the three sections above. Maintenance mode saves on its own.</span>
                    <button type="submit" class="dtr-generate" id="saveSettingsBtn">
                        <i class="fas fa-save mr-1"></i> Save settings
                    </button>
                </div>
            </form>
        </div>

        <div class="col-xl-4">
            {{-- Maintenance --}}
            <div class="dash-card set-maint {{ $isMaintenance ? 'is-on' : '' }}">
                <div class="dash-card-header">
                    <h5><i class="fas fa-tools" style="color: {{ $isMaintenance ? '#c53b30' : 'var(--cpsu-green-600)' }};"></i>Maintenance</h5>
                </div>
                <div class="dash-card-body">
                    <form id="maintenanceSettingsForm" method="POST" action="{{ route('settings.maintenance.update') }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="maintenance" value="0">

                        <div class="set-row pt-0 border-0">
                            <div class="set-row-text">
                                <label class="dtr-label mb-1" for="maintenanceSwitch">System maintenance mode</label>
                                <p class="set-help">{{ $isMaintenance ? 'On: new logins are blocked.' : 'Off: everyone can log in.' }}</p>
                            </div>
                            <label class="set-switch is-danger">
                                <input type="checkbox" id="maintenanceSwitch" name="maintenance" value="1" {{ $isMaintenance ? 'checked' : '' }}>
                                <span class="set-switch-track" aria-hidden="true"></span>
                                <span class="set-switch-text" data-on="On" data-off="Off"></span>
                            </label>
                        </div>

                        <div class="set-warn">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Changes save automatically. When enabled, the login form and Google sign-in are replaced by the Under Maintenance page for everyone, including administrators. Keep this session open so you can turn maintenance mode off again.</span>
                        </div>
                        <p id="maintenanceSaving" class="set-help text-success mt-2 mb-0 d-none">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Saving maintenance setting...
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('systemSettingsForm').addEventListener('submit', function () {
            const btn = document.getElementById('saveSettingsBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
        });

        // Time entry restriction: show what the chosen level does.
        document.querySelectorAll('input[name="te_rstrct_lvl"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                document.getElementById('teRestrictionHelp').textContent = radio.dataset.help;
            });
        });

        // "N selected" next to the access lists.
        document.querySelectorAll('.set-count').forEach(function (badge) {
            const select = document.getElementById(badge.dataset.countFor);
            const update = function () {
                const n = select.selectedOptions.length;
                badge.textContent = '(' + n + ' selected)';
            };
            update();
            $(select).on('change', update);
        });

        // Maintenance mode saves as soon as it is switched.
        const maintenanceSwitch = document.getElementById('maintenanceSwitch');
        let isSubmitting = false;
        maintenanceSwitch.addEventListener('change', function () {
            if (isSubmitting) {
                return;
            }
            isSubmitting = true;
            maintenanceSwitch.disabled = true;
            // A disabled checkbox isn't posted, so carry its value in the hidden field.
            maintenanceSwitch.form.querySelector('input[type="hidden"][name="maintenance"]').value = maintenanceSwitch.checked ? 1 : 0;
            document.getElementById('maintenanceSaving').classList.remove('d-none');
            document.getElementById('maintenanceSettingsForm').submit();
        });
    });
</script>
@endsection
