@extends('layouts.master')

@section('body')
@php
    // [column prefix, title, icon, has a course field] — the save script posts the input's name as the column.
    $basicLevels = [
        ['elem', 'Elementary', 'fa-school', false],
        ['sec', 'Secondary', 'fa-book-reader', false],
        ['voc', 'Vocational / trade course', 'fa-tools', true],
    ];

    // College and graduate studies are stored as comma-separated lists, one item per entry.
    $entries = function ($p) use ($educBg) {
        $cols = ['school', 'course', 'period', 'level', 'grad', 'honor'];
        $lists = collect($cols)->mapWithKeys(fn ($c) => [$c => explode(',', $educBg->{$p . '_' . $c})]);
        return collect($lists['school'])->keys()->map(
            fn ($i) => collect($cols)->mapWithKeys(fn ($c) => [$c => trim($lists[$c][$i] ?? '')])->all()
        );
    };
    $blank = array_fill_keys(['school', 'course', 'period', 'level', 'grad', 'honor'], '');
    $repeatables = [
        ['coll', 'College', 'fa-university', 'add-row-college', 'college-container', 'update-child', 'btn-delete', 'Add college'],
        ['grad', 'Graduate studies', 'fa-user-graduate', 'add-row-graduate', 'graduate-container', 'update-grad', 'btn-delete-grad', 'Add graduate study'],
    ];
@endphp
<div class="container-fluid dash">
    @include('emp.partials.pds-head', ['pdsTitle' => 'Educational Background', 'saveUrls' => [route('educBgUpdate'), route('educBgUpdateArray'), route('educBgUpdateGraduateArray')]])

    <div class="row">
        @include('emp.submenu-side')
        <div class="col-lg-9 dtr-form pds-form">
            @foreach($basicLevels as [$p, $title, $icon, $hasCourse])
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5><i class="fas {{ $icon }}" style="color: var(--cpsu-green-600);"></i>{{ $title }}</h5>
                </div>
                <div class="dash-card-body">
                    <div class="form-row">
                        <div class="col-md-{{ $hasCourse ? 6 : 12 }} dtr-field">
                            <label class="dtr-label" for="{{ $p }}_school">Name of school <span class="font-weight-normal">(write in full)</span></label>
                            <input type="text" value="{{ $educBg->{$p . '_school'} }}" name="{{ $p }}_school" id="{{ $p }}_school" data-column-id="{{ $empid }}" class="form-control update-field" placeholder="N/A">
                        </div>
                        @if($hasCourse)
                        <div class="col-md-6 dtr-field">
                            <label class="dtr-label" for="{{ $p }}_course">Course</label>
                            <input type="text" value="{{ $educBg->{$p . '_course'} }}" name="{{ $p }}_course" id="{{ $p }}_course" data-column-id="{{ $empid }}" class="form-control update-field" placeholder="N/A">
                        </div>
                        @endif
                        <div class="col-md-3 col-6 dtr-field">
                            <label class="dtr-label" for="{{ $p }}_period">Period of attendance</label>
                            <input type="text" value="{{ $educBg->{$p . '_period'} }}" name="{{ $p }}_period" id="{{ $p }}_period" data-column-id="{{ $empid }}" class="form-control update-field" placeholder="e.g. 2009-2015" inputmode="numeric" oninput="restrictInput(this); validateDateRange(this)" onblur="this.reportValidity()">
                        </div>
                        <div class="col-md-3 col-6 dtr-field">
                            <label class="dtr-label" for="{{ $p }}_grad">Year graduated</label>
                            <input type="number" value="{{ $educBg->{$p . '_grad'} }}" name="{{ $p }}_grad" id="{{ $p }}_grad" data-column-id="{{ $empid }}" class="form-control update-field" placeholder="N/A">
                        </div>
                        <div class="col-md-6 dtr-field">
                            <label class="dtr-label" for="{{ $p }}_level">Highest level / units earned <span class="font-weight-normal">(if not graduated)</span></label>
                            <input type="text" value="{{ $educBg->{$p . '_level'} }}" name="{{ $p }}_level" id="{{ $p }}_level" data-column-id="{{ $empid }}" class="form-control update-field" placeholder="N/A">
                        </div>
                        <div class="col-12 dtr-field">
                            <label class="dtr-label" for="{{ $p }}_honor">Scholarship / academic honors received</label>
                            <input type="text" value="{{ $educBg->{$p . '_honor'} }}" name="{{ $p }}_honor" id="{{ $p }}_honor" data-column-id="{{ $empid }}" class="form-control update-field" placeholder="N/A">
                        </div>
                    </div>
                </div>
            </div>
            @endforeach

            @foreach($repeatables as [$p, $title, $icon, $addId, $containerId, $inputClass, $deleteClass, $addLabel])
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5><i class="fas {{ $icon }}" style="color: var(--cpsu-green-600);"></i>{{ $title }}</h5>
                    <button type="button" id="{{ $addId }}" class="lv-btn">
                        <i class="fas fa-plus"></i> {{ $addLabel }}
                    </button>
                </div>
                <div class="dash-card-body">
                    <div id="{{ $containerId }}" class="edu-entries">
                        @foreach($entries($p) as $index => $values)
                            @include('emp.partials.educ-entry', ['v' => $values, 'removable' => $index > 0])
                        @endforeach
                    </div>
                    <template id="{{ $containerId }}-template">
                        @include('emp.partials.educ-entry', ['v' => $blank, 'removable' => true])
                    </template>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
<script>
    // Period of attendance for Elementary / Secondary / Vocational: YYYY-YYYY.
    // Checked while typing, but the message only pops up when leaving the field.
    function validateDateRange(input) {
        const value = input.value;
        if (value === '') {
            input.setCustomValidity('');
            return;
        }
        if (/^\d{4}-\d{4}$/.test(value)) {
            const [startYear, endYear] = value.split('-').map(Number);
            input.setCustomValidity(startYear < 1900 || endYear > 2099 || startYear > endYear
                ? 'Please enter a valid year range (YYYY-YYYY).'
                : '');
        } else {
            input.setCustomValidity('Please enter the date range in YYYY-YYYY format.');
        }
    }

    function restrictInput(input) {
        input.value = input.value.replace(/[^0-9-]/g, '');
    }
</script>
@endsection
