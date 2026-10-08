{{--
    One college / graduate studies entry. Also rendered empty inside a <template>
    that educbgScript clones for the "Add" buttons.
    $p           - column prefix: coll | grad
    $inputClass  - update-child (college) | update-grad (graduate); the script saves on input
    $deleteClass - btn-delete | btn-delete-grad; removes the closest .form-row
    $v           - values keyed school, course, period, level, grad, honor
    $removable   - the first entry has no remove button
--}}
<div class="form-row edu-entry">
    <div class="col-12 edu-entry-head">
        <span class="edu-entry-num"></span>
        @if($removable)
            <button type="button" class="lv-icon-btn is-danger {{ $deleteClass }}" title="Remove this entry" aria-label="Remove this entry">
                <i class="fas fa-trash"></i>
            </button>
        @endif
    </div>
    <div class="col-md-6 dtr-field">
        <label class="dtr-label">Name of school <span class="font-weight-normal">(write in full)</span></label>
        <input type="text" value="{{ $v['school'] }}" name="{{ $p }}_school[]" class="form-control {{ $inputClass }}" placeholder="N/A" aria-label="Name of school">
    </div>
    <div class="col-md-6 dtr-field">
        <label class="dtr-label">Degree / course</label>
        <input type="text" value="{{ $v['course'] }}" name="{{ $p }}_course[]" class="form-control {{ $inputClass }}" placeholder="N/A" aria-label="Degree or course">
    </div>
    <div class="col-md-3 col-6 dtr-field">
        <label class="dtr-label">Period of attendance</label>
        <input type="text" value="{{ $v['period'] }}" name="{{ $p }}_period[]" class="form-control {{ $inputClass }}" placeholder="e.g. 2015-2019" aria-label="Period of attendance">
    </div>
    <div class="col-md-3 col-6 dtr-field">
        <label class="dtr-label">Year graduated</label>
        <input type="number" value="{{ $v['grad'] }}" name="{{ $p }}_grad[]" class="form-control {{ $inputClass }}" placeholder="N/A" aria-label="Year graduated">
    </div>
    <div class="col-md-6 dtr-field">
        <label class="dtr-label">Highest level / units earned <span class="font-weight-normal">(if not graduated)</span></label>
        <input type="text" value="{{ $v['level'] }}" name="{{ $p }}_level[]" class="form-control {{ $inputClass }}" placeholder="N/A" aria-label="Highest level or units earned">
    </div>
    <div class="col-12 dtr-field">
        <label class="dtr-label">Scholarship / academic honors received</label>
        <input type="text" value="{{ $v['honor'] }}" name="{{ $p }}_honor[]" class="form-control {{ $inputClass }}" placeholder="N/A" aria-label="Scholarship or academic honors received">
    </div>
</div>
