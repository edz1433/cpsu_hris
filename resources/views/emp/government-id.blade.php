@extends('layouts.master')

@section('body')
@php
    // Stored as one comma-separated list; each input saves its own slot (data-array).
    $govid = explode(',', $govids->govid);
    $idTypes = ['Passport', 'GSIS', 'SSS', 'UMID', 'PRC', "Driver's License", 'PhilSys National ID', 'Postal ID', "Voter's ID", 'TIN ID'];
@endphp
<div class="container-fluid dash">
    @include('emp.partials.pds-head', ['pdsTitle' => 'Government Issued ID', 'saveUrls' => [route('update.govids')]])

    <div class="row">
        @include('emp.submenu-side')
        <div class="col-lg-9 dtr-form pds-form">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5><i class="fas fa-id-card" style="color: var(--cpsu-green-600);"></i>Government issued ID</h5>
                    <span class="dash-card-hint d-none d-sm-inline">Shown on page 4 of the PDS</span>
                </div>
                <div class="dash-card-body">
                    <p class="pds-hint mt-0 mb-3">One valid ID (e.g. passport, GSIS, SSS, PRC, driver's license). Please indicate the ID number and the date of issuance.</p>
                    <div class="form-row">
                        <div class="col-md-4 dtr-field">
                            <label class="dtr-label" for="govid-0">Government issued ID</label>
                            <input class="form-control input-details updated-data" type="text" name="govid_0" data-array="0" value="{{ $govid[0] ?? '' }}" id="govid-0" list="govIdTypes" placeholder="e.g. Passport" autocomplete="off">
                            <datalist id="govIdTypes">
                                @foreach($idTypes as $type)
                                    <option value="{{ $type }}">
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-md-4 dtr-field">
                            <label class="dtr-label" for="govid-1">ID / license / passport no.</label>
                            <input class="form-control input-details updated-data" type="text" name="govid_1" data-array="1" value="{{ $govid[1] ?? '' }}" id="govid-1" placeholder="N/A" autocomplete="off">
                        </div>
                        <div class="col-md-4 dtr-field">
                            <label class="dtr-label" for="govid-2">Date / place of issuance</label>
                            <input class="form-control input-details updated-data" type="text" name="govid_2" data-array="2" value="{{ $govid[2] ?? '' }}" id="govid-2" placeholder="e.g. 05/14/2021 / Bacolod City" autocomplete="off">
                        </div>
                    </div>
                    <p class="pds-hint mb-0">Commas are removed as you type because they separate the saved values; write the date and place with a space or slash instead.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
