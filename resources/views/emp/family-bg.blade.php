@extends('layouts.master')

@section('body')
@php
    // [column, label] — the save script posts the input's name as the column.
    $spouseName = [
        ['spouse_sname', 'Surname'],
        ['spouse_fname', 'First name'],
        ['spouse_mname', 'Middle name'],
        ['spouse_ext', 'Name extension'],
    ];
    $spouseWork = [
        ['occupation', 'Occupation'],
        ['bus_name', 'Employer / business name'],
        ['bus_address', 'Business address'],
        ['telephone', 'Telephone no.'],
    ];
    $father = [
        ['father_sname', 'Surname'],
        ['father_fname', 'First name'],
        ['father_mname', 'Middle name'],
        ['father_ext', 'Name extension'],
    ];
    $mother = [
        ['mother_sname', 'Surname'],
        ['mother_fname', 'First name'],
        ['mother_mname', 'Middle name'],
    ];

    $names = explode(',', $familyBg->name_child);
    $dates = explode(',', $familyBg->date_birth);
@endphp
<div class="container-fluid dash">
    @include('emp.partials.pds-head', ['pdsTitle' => 'Family Background', 'saveUrls' => [route('familyBgUpdate'), route('update-child')]])

    <div class="row">
        @include('emp.submenu-side')
        <div class="col-lg-9 dtr-form pds-form">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5><i class="fas fa-ring" style="color: var(--cpsu-green-600);"></i>Spouse</h5>
                    <span class="dash-card-hint d-none d-sm-inline">Leave blank if not applicable</span>
                </div>
                <div class="dash-card-body">
                    <div class="form-row">
                        @foreach($spouseName as [$column, $label])
                            <div class="col-md-3 col-6 dtr-field">
                                <label class="dtr-label" for="{{ $column }}">{{ $label }}</label>
                                <input type="text" value="{{ $familyBg->$column }}" name="{{ $column }}" id="{{ $column }}" data-column-id="{{ $empid }}" class="form-control update-field" placeholder="N/A">
                            </div>
                        @endforeach
                    </div>
                    <div class="pds-subhead">Work</div>
                    <div class="form-row">
                        @foreach($spouseWork as [$column, $label])
                            <div class="col-md-6 dtr-field">
                                <label class="dtr-label" for="{{ $column }}">{{ $label }}</label>
                                <input type="text" value="{{ $familyBg->$column }}" name="{{ $column }}" id="{{ $column }}" data-column-id="{{ $empid }}" class="form-control update-field" placeholder="N/A">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="dash-card">
                <div class="dash-card-header">
                    <h5><i class="fas fa-child" style="color: var(--cpsu-green-600);"></i>Children</h5>
                    <button type="button" id="add-row-familybg" class="lv-btn">
                        <i class="fas fa-plus"></i> Add child
                    </button>
                </div>
                <div class="dash-card-body">
                    <div class="fam-kids-head" aria-hidden="true">
                        <span>Full name</span>
                        <span>Date of birth</span>
                        <span></span>
                    </div>
                    <div id="form-container">
                        @foreach($names as $index => $name)
                            @if(isset($dates[$index]))
                                <div class="form-row fam-kid" data-index="{{ $index }}">
                                    <input type="text" value="{{ trim($name) }}" name="name_child[]" class="form-control update-child" data-index="{{ $index }}" placeholder="N/A" aria-label="Child's full name">
                                    <input type="date" value="{{ trim($dates[$index]) }}" name="date_birth[]" class="form-control update-child" data-index="{{ $index }}" aria-label="Child's date of birth">
                                    @if($index > 0)
                                        <button type="button" class="lv-icon-btn is-danger btn-delete" title="Remove child" aria-label="Remove child">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @else
                                        <span></span>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <p class="pds-hint mb-0 mt-2">Write each child's full name and list all of them. Changes save as you type.</p>
                </div>
            </div>

            <div class="dash-card">
                <div class="dash-card-header">
                    <h5><i class="fas fa-user-friends" style="color: var(--cpsu-green-600);"></i>Parents</h5>
                </div>
                <div class="dash-card-body">
                    <div class="pds-subhead mt-0 pt-0 border-0">Father</div>
                    <div class="form-row">
                        @foreach($father as [$column, $label])
                            <div class="col-md-3 col-6 dtr-field">
                                <label class="dtr-label" for="{{ $column }}">{{ $label }}</label>
                                <input type="text" value="{{ $familyBg->$column }}" name="{{ $column }}" id="{{ $column }}" data-column-id="{{ $empid }}" data-column-name="{{ $column }}" class="form-control update-field" placeholder="N/A">
                            </div>
                        @endforeach
                    </div>
                    <div class="pds-subhead">Mother's maiden name</div>
                    <div class="form-row">
                        @foreach($mother as [$column, $label])
                            <div class="col-md-3 {{ $loop->last ? 'col-12' : 'col-6' }} dtr-field">
                                <label class="dtr-label" for="{{ $column }}">{{ $label }}</label>
                                <input type="text" value="{{ $familyBg->$column }}" name="{{ $column }}" id="{{ $column }}" data-column-id="{{ $empid }}" data-column-name="{{ $column }}" class="form-control update-field" placeholder="N/A">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
