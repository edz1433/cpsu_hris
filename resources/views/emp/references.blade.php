@extends('layouts.master')

@section('body')
@php
    // Stored as semicolon-separated lists; each input saves its own slot (data-array).
    $refname = explode(';', $references->refname);
    $refadd = explode(';', $references->refadd);
    $reftelno = explode(';', $references->reftelno);
@endphp
<div class="container-fluid dash">
    @include('emp.partials.pds-head', ['pdsTitle' => 'References', 'saveUrls' => [route('update.references')]])

    <div class="row">
        @include('emp.submenu-side')
        <div class="col-lg-9 dtr-form pds-form">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5><i class="fas fa-address-book" style="color: var(--cpsu-green-600);"></i>Character references</h5>
                    <span class="dash-card-hint d-none d-sm-inline">Three people who can vouch for you</span>
                </div>
                <div class="dash-card-body">
                    <p class="pds-hint mt-0 mb-3">Persons not related by consanguinity or affinity to the applicant / appointee.</p>
                    @for($i = 0; $i < 3; $i++)
                        <div class="ref-item">
                            <span class="ref-num" aria-hidden="true">{{ $i + 1 }}</span>
                            <div class="form-row flex-grow-1">
                                <div class="col-md-4 dtr-field">
                                    <label class="dtr-label" for="refname-{{ $i }}">Name</label>
                                    <input class="form-control input-details updated-data" type="text" name="refname_{{ $i }}" data-array="{{ $i }}" value="{{ $refname[$i] ?? '' }}" id="refname-{{ $i }}" placeholder="Full name" autocomplete="off">
                                </div>
                                <div class="col-md-5 dtr-field">
                                    <label class="dtr-label" for="refadd-{{ $i }}">Address</label>
                                    <input class="form-control input-details updated-data" type="text" name="refadd_{{ $i }}" data-array="{{ $i }}" value="{{ $refadd[$i] ?? '' }}" id="refadd-{{ $i }}" placeholder="Barangay, city / municipality, province" autocomplete="off">
                                </div>
                                <div class="col-md-3 dtr-field">
                                    <label class="dtr-label" for="reftelno-{{ $i }}">Telephone / mobile no.</label>
                                    <input class="form-control input-details updated-data" type="text" name="reftelno_{{ $i }}" data-array="{{ $i }}" value="{{ $reftelno[$i] ?? '' }}" id="reftelno-{{ $i }}" inputmode="tel" placeholder="N/A" autocomplete="off">
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
