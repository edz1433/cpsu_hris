@extends('layouts.master')

@section('body')
@php
    $isEdit = isset($workexperienceedit);
    $isAdmin = $guard == "web";
    $field = fn ($name) => old($name, $isEdit ? $workexperienceedit->$name : '');
    $listUrl = $isAdmin ? route('work-experience', $employee->id) : route('work-experience');
    $listaccom = $isEdit && isset($workexperienceedit->list_accom) ? explode(';', $workexperienceedit->list_accom) : [];
    $summary = $isEdit ? str_replace(['<br>', '<br/>', '<br />'], "\n", (string) $workexperienceedit->actual_summary) : '';
    // Collapsed when there is already a list to look at and nothing is being edited.
    $formOpen = $isEdit || count($workexperience) == 0 || $errors->any();

    $fmtMonth = function ($value) {
        if (empty($value)) return null;
        try { return \Carbon\Carbon::parse($value)->format('M Y'); } catch (\Exception $e) { return $value; }
    };
    $duration = function ($from, $to) {
        try {
            $diff = \Carbon\Carbon::parse($from)->diff($to ? \Carbon\Carbon::parse($to) : now());
        } catch (\Exception $e) {
            return null;
        }
        $parts = array_filter([
            $diff->y ? $diff->y . ' ' . ($diff->y == 1 ? 'yr' : 'yrs') : null,
            $diff->m ? $diff->m . ' ' . ($diff->m == 1 ? 'mo' : 'mos') : null,
        ]);
        return $parts ? implode(' ', $parts) : 'Under a month';
    };
@endphp
<div class="container-fluid dash">
    @include('emp.partials.pds-head', [
        'pdsTitle' => 'Work Experience',
        'saveUrls' => [],
        'pdsNote' => 'every position held, starting with the most recent.',
    ])

    <div class="row">
        @include('emp.submenu-side')
        <div class="col-lg-9">
            <div class="dash-card {{ $isEdit ? 'um-editing' : '' }}" id="workFormCard">
                <div class="dash-card-header">
                    <h5>
                        <i class="fas {{ $isEdit ? 'fa-pen' : 'fa-plus-circle' }}" style="color: var(--cpsu-green-600);"></i>
                        {{ $isEdit ? 'Edit work experience' : 'Add work experience' }}
                    </h5>
                    @if($isEdit)
                        <a href="{{ $listUrl }}" class="dash-card-hint">Cancel</a>
                    @else
                        <button type="button" class="lv-btn {{ $formOpen ? '' : 'is-primary' }}" data-toggle="collapse" data-target="#workForm" aria-expanded="{{ $formOpen ? 'true' : 'false' }}" aria-controls="workForm" id="workFormToggle">
                            <i class="fas {{ $formOpen ? 'fa-chevron-up' : 'fa-plus' }}"></i> <span>{{ $formOpen ? 'Hide form' : 'Add position' }}</span>
                        </button>
                    @endif
                </div>
                <div class="collapse {{ $formOpen ? 'show' : '' }}" id="workForm">
                <div class="dash-card-body">
                    <form class="dtr-form pds-form" action="{{ $isEdit ? route('workexperienceUpdate', $workexperienceedit->id) : route('workexperienceCreate') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if($isEdit)
                            <input type="hidden" name="id" value="{{ $workexperienceedit->id }}">
                        @endif
                        <input type="hidden" name="empid" value="{{ $employee->emp_ID }}">

                        <div class="form-row">
                            <div class="col-md-6 dtr-field">
                                <label class="dtr-label" for="position">Position title <span class="font-weight-normal">(write in full, do not abbreviate)</span></label>
                                <input type="text" name="position" id="position" class="form-control @error('position') is-invalid @enderror" value="{{ $field('position') }}" placeholder="e.g. Administrative Officer IV" autocomplete="off" required>
                            </div>
                            <div class="col-md-6 dtr-field">
                                <label class="dtr-label" for="department">Department / agency / office / company <span class="font-weight-normal">(in full)</span></label>
                                <input type="text" name="department" id="department" class="form-control @error('department') is-invalid @enderror" value="{{ $field('department') }}" placeholder="e.g. Central Philippines State University" autocomplete="off" required>
                            </div>

                            <div class="col-md-3 col-6 dtr-field">
                                <label class="dtr-label" for="inc_date1">From</label>
                                <input type="date" id="inc_date1" name="inc_date1" class="form-control @error('inc_date1') is-invalid @enderror" value="{{ $field('inc_date1') }}" required>
                            </div>
                            <div class="col-md-3 col-6 dtr-field">
                                <label class="dtr-label" for="inc_date2">To <span class="font-weight-normal">(empty if current)</span></label>
                                <input type="date" id="inc_date2" name="inc_date2" class="form-control @error('inc_date2') is-invalid @enderror" value="{{ $field('inc_date2') }}">
                            </div>
                            <div class="col-md-3 col-6 dtr-field">
                                <label class="dtr-label" for="stat_app">Status of appointment</label>
                                <input type="text" name="stat_app" id="stat_app" class="form-control" value="{{ $field('stat_app') }}" placeholder="e.g. Permanent" autocomplete="off">
                            </div>
                            <div class="col-md-3 col-6 dtr-field">
                                <label class="dtr-label" for="service">Government service</label>
                                <select name="service" id="service" class="form-control @error('service') is-invalid @enderror" required>
                                    <option value="" @if($field('service') === '' || $field('service') === null) selected @endif>Select</option>
                                    <option value="Y" @if($field('service') === 'Y') selected @endif>Yes</option>
                                    <option value="N" @if($field('service') === 'N') selected @endif>No</option>
                                </select>
                            </div>

                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="salary">Monthly salary</label>
                                <div class="wx-money">
                                    <span aria-hidden="true">&#8369;</span>
                                    <input type="text" name="salary" id="salary" class="form-control @error('salary') is-invalid @enderror" value="{{ $field('salary') }}" placeholder="0" inputmode="numeric" autocomplete="off" oninput="autoFormatNumber(this)" required>
                                </div>
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="sg_grade">Salary grade &amp; step <span class="font-weight-normal">(if applicable)</span></label>
                                <input type="text" name="sg_grade" id="sg_grade" class="form-control" value="{{ $field('sg_grade') }}" placeholder="Format 00-0, e.g. 15-2" autocomplete="off">
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="supervisor">Immediate supervisor</label>
                                <input type="text" name="supervisor" id="supervisor" class="form-control" value="{{ $field('supervisor') }}" placeholder="N/A" autocomplete="off">
                            </div>

                            <div class="col-12 dtr-field">
                                <label class="dtr-label" for="attachment">Supporting document <span class="font-weight-normal">(PDF, optional)</span></label>
                                <label class="eli-file @error('attachment') is-invalid @enderror" for="attachment">
                                    <i class="fas fa-file-upload"></i>
                                    <span id="attachmentName">
                                        {{ $isEdit && $workexperienceedit->attachment ? 'Choose a PDF to replace the current file' : 'Choose a PDF file' }}
                                    </span>
                                    <span class="eli-file-btn">Browse</span>
                                </label>
                                <input type="file" name="attachment" id="attachment" class="eli-file-input" accept="application/pdf">
                                @if($isEdit && $workexperienceedit->attachment)
                                    <p class="pds-hint mt-1 mb-0">Leave empty to keep the current file.</p>
                                @endif
                            </div>
                        </div>

                        <div class="pds-subhead">Work experience sheet <span class="font-weight-normal" style="letter-spacing: 0; text-transform: none;">(attachment to CS Form No. 212)</span></div>
                        <div class="form-row">
                            <div class="col-md-6 dtr-field">
                                <span class="dtr-label">Accomplishments and contributions <span class="font-weight-normal">(if any)</span></span>
                                <ol class="wx-accom">
                                    @for ($i = 0; $i < 8; $i++)
                                        <li>
                                            <input type="text" name="list_accom[{{ $i }}]" class="form-control"
                                                placeholder="N/A"
                                                value="{{ old('list_accom.' . $i, isset($listaccom[$i]) ? trim($listaccom[$i]) : '') }}"
                                                aria-label="Accomplishment {{ $i + 1 }}" autocomplete="off">
                                        </li>
                                    @endfor
                                </ol>
                            </div>
                            <div class="col-md-6 dtr-field d-flex flex-column">
                                <label class="dtr-label" for="actual_summary">Summary of actual duties</label>
                                <textarea name="actual_summary" id="actual_summary" class="form-control wx-summary" rows="13" placeholder="Describe the duties you actually performed in this position.">{{ old('actual_summary', $summary) }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end align-items-center" style="gap: 8px;">
                            @if($isEdit)
                                <a href="{{ $listUrl }}" class="lv-btn">Cancel</a>
                            @endif
                            <button type="submit" name="btn-submit" class="dtr-generate">
                                <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Save changes' : 'Add work experience' }}
                            </button>
                        </div>
                    </form>
                </div>
                </div>
            </div>

            <div class="dash-card">
                <div class="dash-card-header flex-wrap">
                    <h5><i class="fas fa-briefcase" style="color: var(--cpsu-green-600);"></i>Positions held <span class="eli-count">{{ count($workexperience) }}</span></h5>
                    @if(count($workexperience) > 0)
                        <label class="emp-search mb-0" for="workSearch">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input type="search" name="table_search" id="workSearch" placeholder="Search positions, offices..." autocomplete="off" aria-label="Search work experience">
                        </label>
                    @endif
                </div>
                <div class="dash-card-body">
                    @forelse($workexperience as $work)
                        @php
                            $isCurrent = $work->inc_date2 == null;
                            $details = [
                                ['Monthly salary', $work->salary ? '₱' . $work->salary : null],
                                ['Salary grade & step', $work->sg_grade],
                                ['Status of appointment', $work->stat_app],
                                ['Government service', ($work->service == "Y") ? 'Yes' : 'No'],
                                ['Immediate supervisor', $work->supervisor],
                            ];
                        @endphp
                        <article class="eli-item workexperience-row row-{{ $work->id }} {{ $isEdit && $workexperienceedit->id == $work->id ? 'is-editing' : '' }}">
                            <div class="eli-item-head">
                                <div style="min-width: 0;">
                                    <h6 class="eli-title mb-0">{{ $work->position ?: 'Untitled position' }}</h6>
                                    <div class="wx-dept">{{ $work->department }}</div>
                                    <div class="wx-when">
                                        <i class="far fa-calendar-alt"></i>
                                        {{ $fmtMonth($work->inc_date1) }} &ndash; {{ $isCurrent ? 'Present' : $fmtMonth($work->inc_date2) }}
                                        @if($d = $duration($work->inc_date1, $work->inc_date2))<span class="wx-dur">&middot; {{ $d }}</span>@endif
                                        @if($isCurrent)<span class="lv-pill is-info ml-1">Current</span>@endif
                                    </div>
                                </div>
                                <div class="d-flex flex-column align-items-end" style="gap: 8px;">
                                    <span class="lv-row-actions">
                                        @if($isAdmin || $guard == "employee")
                                            <a href="{{ route('workexperienceEdit', ['id' => $empid, 'eid' => $work->id]) }}" class="lv-icon-btn" title="Edit" aria-label="Edit">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                        @endif
                                        @if($isAdmin)
                                            <button type="button" class="lv-icon-btn workexperience_approve" value="{{ $work->id }}" title="Mark as reviewed" aria-label="Mark as reviewed">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            @if ($work->status == 0)
                                                <button type="button" class="lv-icon-btn" data-toggle="modal" data-target="#workexp-modal" onclick="openworkexpModal({{ $work->id }})" title="Cancel with remarks" aria-label="Cancel with remarks">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            @endif
                                        @endif
                                        @if($isAdmin || ($guard == "employee" && $work->status == 0))
                                            <button type="button" class="lv-icon-btn is-danger workexperience_delete" value="{{ $work->id }}" title="Delete" aria-label="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </span>
                                    @if ($work->status == 0)
                                        <span class="lv-pill is-start" id="status-{{ $work->id }}">To be reviewed</span>
                                    @elseif($work->status == 1)
                                        <span class="lv-pill is-added">Reviewed</span>
                                    @else
                                        <span class="lv-pill is-deducted">Canceled</span>
                                    @endif
                                </div>
                            </div>

                            <dl class="eli-details">
                                @foreach($details as [$label, $value])
                                    <div>
                                        <dt>{{ $label }}</dt>
                                        <dd class="{{ $value ? '' : 'lv-muted' }}">{{ $value ?: 'N/A' }}</dd>
                                    </div>
                                @endforeach
                            </dl>

                            @if($work->status != 0 && $work->status != 1)
                                <div class="eli-remarks"><b>Remarks:</b> {{ $work->remarks ?: '—' }}</div>
                            @endif

                            @if(!empty($work->attachment))
                                <div class="eli-item-foot">
                                    <a href="#" class="lv-btn" data-toggle="modal" data-target="#pdfModal"
                                       data-label="{{ $work->position }}"
                                       data-pdf="{{ asset('storage/' . $work->attachment) }}" onclick="showPdfModal(this)">
                                        <i class="fas fa-file-pdf"></i> View document
                                    </a>
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="dtr-empty py-4">
                            <div class="dtr-empty-icon"><i class="fas fa-briefcase"></i></div>
                            <h6>No work experience yet</h6>
                            <p>Add each position with the form above, starting with the most recent.</p>
                        </div>
                    @endforelse
                    <div class="dtr-empty py-4 d-none" id="workNoMatch">
                        <h6>No matches</h6>
                        <p>Nothing here matches that search.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade ev-modal" id="workexp-modal" tabindex="-1" aria-labelledby="workexpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('workexperienceCancel') }}" class="dtr-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="workexpModalLabel"><i class="fas fa-ban"></i>Cancel work experience</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body pb-3">
                    <input type="hidden" name="id" id="workexp-id">
                    <label class="dtr-label" for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-control" rows="3" style="height: auto;" placeholder="Why is this entry being canceled?" required></textarea>
                    <p class="pds-hint mt-2 mb-0">The employee sees these remarks next to the entry.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lv-btn" data-dismiss="modal">Close</button>
                    <button type="submit" class="lv-btn is-danger"><i class="fas fa-ban"></i> Cancel entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade ev-modal" id="pdfModal" tabindex="-1" role="dialog" aria-labelledby="pdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <iframe id="modalPdf" src="" title="Supporting document" style="display: block; width: 100%; height: 75vh; border: 0;"></iframe>
            </div>
        </div>
    </div>
</div>
<script>
    // The modal itself opens through data-toggle; this only fills it in.
    function showPdfModal(link) {
        document.getElementById('pdfModalLabel').innerText = link.getAttribute('data-label');
        document.getElementById('modalPdf').src = link.getAttribute('data-pdf');
    }

    function openworkexpModal(id) {
        document.getElementById('workexp-id').value = id;
        document.getElementById('remarks').value = '';
    }

    function autoFormatNumber(input) {
        let value = input.value.replace(/,/g, '').replace(/\D/g, '');
        input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    document.addEventListener('DOMContentLoaded', function () {
        $('#pdfModal').on('hidden.bs.modal', function () {
            document.getElementById('modalPdf').src = '';
        });
        $('#workexp-modal').on('shown.bs.modal', function () {
            document.getElementById('remarks').focus();
        });

        // Toggle button label follows the form's open/closed state.
        $('#workForm').on('shown.bs.collapse hidden.bs.collapse', function (e) {
            var open = e.type === 'shown';
            var btn = document.getElementById('workFormToggle');
            if (!btn) return;
            btn.classList.toggle('is-primary', !open);
            btn.querySelector('i').className = 'fas ' + (open ? 'fa-chevron-up' : 'fa-plus');
            btn.querySelector('span').textContent = open ? 'Hide form' : 'Add position';
            if (open) document.getElementById('position').focus();
        });

        var fileInput = document.getElementById('attachment');
        fileInput.addEventListener('change', function () {
            var name = fileInput.files.length ? fileInput.files[0].name : 'Choose a PDF file';
            document.getElementById('attachmentName').textContent = name;
            fileInput.previousElementSibling.classList.toggle('has-file', fileInput.files.length > 0);
        });
    });
</script>
@endsection
