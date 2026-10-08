@extends('layouts.master')

@section('body')
@php
    // Stored as three comma-separated lists, one item per row.
    $skillshob = explode(',', $otherinfo->skills_hob);
    $recognition = explode(',', $otherinfo->recognition);
    $memorg = explode(',', $otherinfo->mem_org);
@endphp
<div class="container-fluid dash">
    @include('emp.partials.pds-head', ['pdsTitle' => 'Other Information', 'saveUrls' => [route('update-child-oi')]])

    <div class="row">
        @include('emp.submenu-side')
        <div class="col-lg-9 dtr-form pds-form">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5><i class="fas fa-info-circle" style="color: var(--cpsu-green-600);"></i>Skills, recognition and memberships</h5>
                    <button type="button" id="add-row-familybg" class="lv-btn">
                        <i class="fas fa-plus"></i> Add row
                    </button>
                </div>
                <div class="dash-card-body">
                    <div class="oi-head" aria-hidden="true">
                        <span>Special skills and hobbies</span>
                        <span>Non-academic distinctions / recognition <span class="font-weight-normal">(in full)</span></span>
                        <span>Membership in association / organization <span class="font-weight-normal">(in full)</span></span>
                        <span></span>
                    </div>
                    <div id="form-container">
                        @foreach($skillshob as $index => $name)
                            @if(isset($memorg[$index]))
                                <div class="form-row oi-row" data-index="{{ $index }}">
                                    <input type="text" value="{{ trim($name) }}" name="skills_hob[]" class="form-control update-child" data-index="{{ $index }}" placeholder="N/A" aria-label="Special skill or hobby">
                                    <input type="text" value="{{ trim($recognition[$index] ?? '') }}" name="recognition[]" class="form-control update-child" data-index="{{ $index }}" placeholder="N/A" aria-label="Non-academic distinction or recognition">
                                    <input type="text" value="{{ trim($memorg[$index]) }}" name="mem_org[]" class="form-control update-child" data-index="{{ $index }}" placeholder="N/A" aria-label="Membership in association or organization">
                                    @if($index > 0)
                                        <button type="button" class="lv-icon-btn is-danger btn-delete" title="Remove row" aria-label="Remove row">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @else
                                        <span></span>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <p class="pds-hint mb-0 mt-2">One item per box; a row doesn't need all three filled in. Commas are removed because they separate the saved items.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
