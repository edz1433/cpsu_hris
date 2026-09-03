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

                    <!-- Group 1: Executive / Leadership Positions -->
                    <div class="settings-group">
                        <div class="group-header">Executive / Leadership Positions</div>
                        <div class="row">
                            <div class="col-3">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">SUC President</label>
                                    <select id="sucPresident" class="form-control form-select select2">
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}">{{ ucfirst($emp->fname) }} {{ ucfirst($emp->lname) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-3">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">Vice President of Academic Affairs</label>
                                    <select id="vicePresidentAcademic" class="form-control form-select select2">
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}">{{ ucfirst($emp->fname) }} {{ ucfirst($emp->lname) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-3">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">Vice President of Academic Finance</label>
                                    <select id="vicePresidentFinance" class="form-control form-select select2">
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}">{{ ucfirst($emp->fname) }} {{ ucfirst($emp->lname) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-3">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">HR Head</label>
                                    <select id="vicePresidentFinance" class="form-control form-select select2">
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}">{{ ucfirst($emp->fname) }} {{ ucfirst($emp->lname) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Group 2: Time & Attendance Settings -->
                    <div class="settings-group">
                        <div class="group-header">Time & Attendance Settings</div>
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">Time Entry Restriction</label>
                                    <select id="timerestriction" class="form-control form-select select2">
                                        <option value="0">None</option>
                                        <option value="1">Partial Restriction</option>
                                        <option value="2">Full Restriction</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">HR Kiosk Access</label>
                                    <select id="hrKioskAccess" class="form-control form-select select2" multiple>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->emp_ID }}"
                                                {{ in_array($emp->emp_ID, $kioskAccess ?? []) ? 'selected' : '' }}>
                                                {{ ucfirst($emp->fname) }} {{ ucfirst($emp->lname) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">DTR Full Access</label>
                                    <select id="dtrFullAccess" class="form-control form-select select2" multiple>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}"
                                                {{ in_array($emp->id, $dtrFullAccess ?? []) ? 'selected' : '' }}>
                                                {{ ucfirst($emp->fname) }} {{ ucfirst($emp->lname) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Group 3: Email & Notification Settings -->
                    <div class="settings-group">
                        <div class="group-header">Email & Notification Settings</div>
                        <div class="row">
                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">HR Head Email</label>
                                    <input type="email" id="hr-head-email" class="form-control form-control-sm" placeholder="Enter email">
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">Records Office Email</label>
                                    <input type="email" id="records-office-email" class="form-control form-control-sm" placeholder="Enter email">
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold">Job Portal Email</label>
                                    <input type="email" id="job-portal-email" class="form-control form-control-sm" placeholder="Enter email">
                                </div>
                            </div>
                        </div>
                    </div>

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

                    <!-- Group 5: Kiosk Controls -->
                    <div class="settings-group">
                        <div class="group-header">Kiosk Controls</div>
                        <div class="row">
                            <div class="col-6 col-md-4">
                                <div class="mb-3">
                                    <label class="d-block font-weight-bold mb-2">HR Kiosk Backtrack Sync</label>
                                    <input type="checkbox" id="kioskBacktrackSync" data-bootstrap-switch
                                           data-off-color="danger" data-on-color="success">
                                </div>
                            </div>
                        </div>
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
