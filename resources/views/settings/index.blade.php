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
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h2 class="card-title text-success1"><b>SYSTEM SETTINGS</b></h2>
                </div>
                <div class="card-body bg-form">
                    <div class="row">
                        <div class="col-3">
                            <div class="mb-3">
                                <label class="d-block font-weight-bold">SUC President</label>
                                <select id="sucPresident" class="form-control form-select select2">
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}">{{ ucfirst($emp->fname).' '.ucfirst($emp->lname) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-3">
                            <div class="mb-3">
                                <label class="d-block font-weight-bold">Vice President of Academic Affairs</label>
                                <select id="vicePresidentAcademic" class="form-control form-select select2">
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}">{{ ucfirst($emp->fname).' '.ucfirst($emp->lname) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-3">
                            <div class="mb-3">
                                <label class="d-block font-weight-bold">Vice President of Academic Finance</label>
                                <select id="vicePresidentFinance" class="form-control form-select select2">
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}">{{ ucfirst($emp->fname).' '.ucfirst($emp->lname) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-3">
                            <div class="mb-3">
                                <label class="d-block font-weight-bold">Time Entry Restriction</label>
                                <select id="timerestriction" class="form-control form-select select2">
                                    <option value="0">None</option>
                                    <option value="1">Partial Restriction</option>
                                    <option value="2">Full Restriction</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-3">
                            <div class="mb-3">
                                <label class="d-block font-weight-bold">HR Head</label>
                                <input type="text" id="hr-head-email" class="form-control form-control-sm" placeholder="Enter email">
                            </div>
                        </div>

                        <div class="col-3">
                            <div class="mb-3">
                                <label class="d-block font-weight-bold">Records office email</label>
                                <input type="text" id="records-office-email" class="form-control form-control-sm" placeholder="Enter email">
                            </div>
                        </div>

                        <div class="col-3">
                            <div class="mb-3">
                                <label class="d-block font-weight-bold">Job portal email</label>
                                <input type="text" id="job-portal-email" class="form-control form-control-sm" placeholder="Enter email">
                            </div>
                        </div>

                        <div class="col-3">
                            <div class="mb-3">
                                <div class="row">
                                    <div class="col-6">
                                        <label class="d-block font-weight-bold mb-2">System Maintenance</label>
                                        <input type="checkbox" id="maintenanceSwitch" data-bootstrap-switch
                                            data-off-color="danger" data-on-color="success">
                                    </div>
                                    <div class="col-6">
                                        <label class="d-block font-weight-bold">HR Kiosk Backtrack Sync.</label>
                                        <input type="checkbox" id="kioskBacktrackSync" data-bootstrap-switch
                                            data-off-color="danger" data-on-color="success">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="mb-3">
                                <label class="d-block font-weight-bold">HR Kiosk Access</label>
                                <select id="hrKioskAccess" class="form-control form-select select2" multiple>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->emp_ID }}"
                                            {{ in_array($emp->emp_ID, $kioskAccess ?? []) ? 'selected' : '' }}>
                                            {{ ucfirst($emp->fname).' '.ucfirst($emp->lname) }}
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
                                            {{ ucfirst($emp->fname).' '.ucfirst($emp->lname) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection