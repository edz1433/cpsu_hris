@extends('layouts.master')

@section('body')
@php
    $current_route = request()->route()->getName();
    $isEdit = $current_route == 'officeEdit';
    // Capitalizes the first letter of each word as the user types (kept from the original form).
    $titleCase = "var words = this.value.split(' '); for(var i = 0; i < words.length; i++){ words[i] = words[i].substr(0,1).toUpperCase() + words[i].substr(1); } this.value = words.join(' ');";
    $personName = fn ($first, $last) => trim(($first ?? '') . ' ' . ($last ?? ''));
@endphp
<div class="container-fluid dash">
    <div class="dtr-head">
        <div>
            <h1>Offices</h1>
            <p>University offices, their heads and officers-in-charge.</p>
        </div>
        @if($isEdit)
            <a href="{{ route('officeList') }}" class="lv-btn"><i class="fas fa-plus"></i> New office</a>
        @endif
    </div>

    <div class="row">
        <div class="col-xl-4 col-lg-5">
            <div class="dash-card {{ $isEdit ? 'um-editing' : '' }}">
                <div class="dash-card-header">
                    <h5>
                        <i class="fas {{ $isEdit ? 'fa-pen' : 'fa-plus-circle' }}" style="color: var(--cpsu-green-600);"></i>
                        {{ $isEdit ? 'Edit office' : 'New office' }}
                    </h5>
                    @if($isEdit)
                        <a href="{{ route('officeList') }}" class="dash-card-hint">Cancel</a>
                    @endif
                </div>
                <div class="dash-card-body">
                    <form class="dtr-form" action="{{ $isEdit ? route('officeUpdate') : route('officeCreate') }}" method="POST">
                        @csrf
                        <input type="hidden" name="oid" value="{{ $isEdit ? $offEdit->id : '' }}">

                        <div class="dtr-field">
                            <label class="dtr-label" for="OfficeName">Office name</label>
                            <input type="text" class="form-control @error('OfficeName') is-invalid @enderror" id="OfficeName" name="OfficeName" value="{{ old('OfficeName', $isEdit ? $offEdit->office_name : '') }}" oninput="{{ $titleCase }}" placeholder="e.g. Management Information System" autocomplete="off" required>
                        </div>

                        <div class="dtr-field">
                            <label class="dtr-label" for="OfficeAbbreviation">Abbreviation</label>
                            <input type="text" class="form-control @error('OfficeAbbreviation') is-invalid @enderror" id="OfficeAbbreviation" name="OfficeAbbreviation" value="{{ old('OfficeAbbreviation', $isEdit ? $offEdit->office_abbr : '') }}" oninput="{{ $titleCase }}" placeholder="e.g. MIS" autocomplete="off" required>
                        </div>

                        <div class="dtr-field">
                            <label class="dtr-label" for="office_head_id">Office head @unless($isEdit)<span class="font-weight-normal">(optional)</span>@endunless</label>
                            <select class="form-control select2 @error('office_head_id') is-invalid @enderror" id="office_head_id" name="office_head_id" style="width: 100%;" @if($isEdit) required @endif>
                                <option value="">Select employee</option>
                                @foreach($employee as $emp)
                                    <option value="{{ $emp->id }}" @if($isEdit && $emp->id == $offEdit->office_head_id) selected @endif>{{ $emp->emp_ID }} - {{ $emp->lname }} {{ $emp->fname }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="dtr-field">
                            <label class="dtr-label" for="oic_id">Officer-in-charge (OIC) <span class="font-weight-normal">(optional)</span></label>
                            <select class="form-control select2" id="oic_id" name="oic_id" style="width: 100%;">
                                <option value="">Select employee</option>
                                @foreach($employee as $emp)
                                    <option value="{{ $emp->id }}" @if($isEdit && $emp->id == $offEdit->oic_id) selected @endif>{{ $emp->emp_ID }} - {{ $emp->lname }} {{ $emp->fname }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" name="btn-submit" class="dtr-generate w-100">
                            <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Save changes' : 'Add office' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5><i class="fas fa-building" style="color: var(--cpsu-green-600);"></i>Offices</h5>
                    <span class="dash-card-hint">{{ count($office) }} {{ count($office) == 1 ? 'office' : 'offices' }}</span>
                </div>
                <div class="dash-card-body">
                    <div class="table-responsive">
                        <table id="example1" class="table table-hover lv-table">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Office</th>
                                    <th>Office head</th>
                                    <th>OIC</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tbody">
                                @foreach($office as $off)
                                    @php
                                        $head = $personName($off->efname, $off->elname);
                                        $oic = $personName($off->ofname, $off->olname);
                                    @endphp
                                    <tr id="tr-{{ $off->id }}" class="{{ $isEdit && $offEdit->id == $off->id ? 'um-row-active' : '' }}">
                                        <td class="text-center lv-muted lv-num">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="um-name">{{ $off->office_name }}</div>
                                            @if($off->office_abbr)
                                                <span class="um-page mt-1 mb-0">{{ $off->office_abbr }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($head !== '')
                                                <div class="emp-person" style="min-width: 0;">
                                                    <span class="emp-initials" aria-hidden="true">{{ strtoupper(substr($off->efname ?? '', 0, 1) . substr($off->elname ?? '', 0, 1)) }}</span>
                                                    <span>{{ $head }}</span>
                                                </div>
                                            @else
                                                <span class="lv-muted">Not set</span>
                                            @endif
                                        </td>
                                        <td class="{{ $oic !== '' ? '' : 'lv-muted' }}">{{ $oic !== '' ? $oic : '—' }}</td>
                                        <td class="text-center" width="96">
                                            <span class="lv-row-actions">
                                                <a href="{{ route('officeEdit', $off->id) }}" class="lv-icon-btn" title="Edit" aria-label="Edit {{ $off->office_name }}">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <button type="button" value="{{ $off->id }}" class="lv-icon-btn is-danger office-delete" title="Delete" aria-label="Delete {{ $off->office_name }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
