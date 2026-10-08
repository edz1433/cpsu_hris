@extends('layouts.master')

@section('body')
@php
    $isEdit = isset($eligibilityedit);
    $isAdmin = $guard == "web";
    $field = fn ($name) => old($name, $isEdit ? $eligibilityedit->$name : '');
    $listUrl = $isAdmin ? route('eligibility', $employee->id) : route('eligibility');
    $fmtDate = function ($value) {
        if (empty($value)) return null;
        try { return \Carbon\Carbon::parse($value)->format('F d, Y'); } catch (\Exception $e) { return $value; }
    };
@endphp
<div class="container-fluid dash">
    @include('emp.partials.pds-head', [
        'pdsTitle' => 'Eligibility',
        'saveUrls' => [],
        'pdsNote' => 'civil service, board/bar and other eligibilities, each with its certificate.',
    ])

    <div class="row">
        @include('emp.submenu-side')
        <div class="col-lg-9">
            <div class="dash-card {{ $isEdit ? 'um-editing' : '' }}" id="eligibilityFormCard">
                <div class="dash-card-header">
                    <h5>
                        <i class="fas {{ $isEdit ? 'fa-pen' : 'fa-plus-circle' }}" style="color: var(--cpsu-green-600);"></i>
                        {{ $isEdit ? 'Edit eligibility' : 'Add eligibility' }}
                    </h5>
                    @if($isEdit)
                        <a href="{{ $listUrl }}" class="dash-card-hint">Cancel</a>
                    @endif
                </div>
                <div class="dash-card-body">
                    <form class="dtr-form pds-form" action="{{ $isEdit ? route('eligibilityUpdate', $eligibilityedit->id) : route('eligibilityCreate') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if($isEdit)
                            <input type="hidden" name="id" value="{{ $eligibilityedit->id }}">
                        @endif
                        <input type="hidden" name="empid" value="{{ $employee->emp_ID }}">

                        <div class="form-row">
                            <div class="col-12 dtr-field">
                                <label class="dtr-label" for="careereligible">Eligibility</label>
                                <input type="text" name="careereligible" id="careereligible" class="form-control @error('careereligible') is-invalid @enderror" value="{{ $field('careereligible') }}" placeholder="e.g. Career Service Professional, Licensed Professional Teacher" autocomplete="off" required>
                                <p class="pds-hint mt-1 mb-0">Career service / RA 1080 (board/bar) under special laws / CES / CSEE / barangay eligibility / driver's license</p>
                            </div>
                            <div class="col-md-4 col-6 dtr-field">
                                <label class="dtr-label" for="rating">Rating <span class="font-weight-normal">(if applicable)</span></label>
                                <input type="number" name="rating" id="rating" step="0.01" min="0" class="form-control @error('rating') is-invalid @enderror" value="{{ $field('rating') }}" placeholder="N/A" autocomplete="off">
                            </div>
                            <div class="col-md-4 col-6 dtr-field">
                                <label class="dtr-label" for="date_exam">Date of examination / conferment</label>
                                <input type="date" name="date_exam" id="date_exam" class="form-control @error('date_exam') is-invalid @enderror" value="{{ $field('date_exam') }}" required>
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="date_valid">Date of validity <span class="font-weight-normal">(if any)</span></label>
                                <input type="date" name="date_valid" id="date_valid" class="form-control @error('date_valid') is-invalid @enderror" value="{{ $field('date_valid') }}">
                            </div>
                            <div class="col-md-8 dtr-field">
                                <label class="dtr-label" for="place_exam">Place of examination / conferment</label>
                                <input type="text" name="place_exam" id="place_exam" class="form-control @error('place_exam') is-invalid @enderror" value="{{ $field('place_exam') }}" placeholder="e.g. Iloilo City" autocomplete="off" required>
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="number">License number <span class="font-weight-normal">(if applicable)</span></label>
                                <input type="number" name="number" id="number" class="form-control @error('number') is-invalid @enderror" value="{{ $field('number') }}" placeholder="N/A" autocomplete="off">
                            </div>
                            <div class="col-12 dtr-field">
                                <label class="dtr-label" for="attachment">Certificate <span class="font-weight-normal">(PDF)</span></label>
                                <label class="eli-file @error('attachment') is-invalid @enderror" for="attachment">
                                    <i class="fas fa-file-upload"></i>
                                    <span id="attachmentName">
                                        @if($isEdit && $eligibilityedit->attachment)
                                            Choose a PDF to replace the current certificate
                                        @else
                                            Choose a PDF file
                                        @endif
                                    </span>
                                    <span class="eli-file-btn">Browse</span>
                                </label>
                                <input type="file" name="attachment" id="attachment" class="eli-file-input" accept="application/pdf" @if(!$isEdit) required @endif>
                                @if($isEdit && $eligibilityedit->attachment)
                                    <p class="pds-hint mt-1 mb-0">Leave empty to keep the current certificate.</p>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex justify-content-end align-items-center" style="gap: 8px;">
                            @if($isEdit)
                                <a href="{{ $listUrl }}" class="lv-btn">Cancel</a>
                            @endif
                            <button type="submit" name="btn-submit" class="dtr-generate">
                                <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Save changes' : 'Add eligibility' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="dash-card">
                <div class="dash-card-header flex-wrap">
                    <h5><i class="fas fa-certificate" style="color: var(--cpsu-green-600);"></i>Eligibilities <span class="eli-count">{{ count($eligibility) }}</span></h5>
                    @if(count($eligibility) > 0)
                        <label class="emp-search mb-0" for="eliSearch">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input type="search" name="table_search" id="eliSearch" placeholder="Search eligibilities..." autocomplete="off" aria-label="Search eligibilities">
                        </label>
                    @endif
                </div>
                <div class="dash-card-body">
                    @forelse($eligibility as $eli)
                        @php
                            $canChange = $isAdmin || ($guard == "employee" && $eli->status !== 1);
                            $details = [
                                ['Rating', ($eli->rating != NULL) ? $eli->rating : null],
                                ['Date of exam / conferment', $fmtDate($eli->date_exam)],
                                ['Place of exam / conferment', $eli->place_exam],
                                ['License number', $eli->number],
                                ['Valid until', $fmtDate($eli->date_valid)],
                            ];
                        @endphp
                        <article class="eli-item eligibility-row row-{{ $eli->id }} {{ $isEdit && $eligibilityedit->id == $eli->id ? 'is-editing' : '' }}">
                            <div class="eli-item-head">
                                <div style="min-width: 0;">
                                    <h6 class="eli-title">{{ $eli->careereligible ?: 'Untitled eligibility' }}</h6>
                                    @if ($eli->status == 0)
                                        <span class="lv-pill is-start" id="status-{{ $eli->id }}">To be reviewed</span>
                                    @elseif($eli->status == 1)
                                        <span class="lv-pill is-added">Reviewed</span>
                                    @else
                                        <span class="lv-pill is-deducted">Canceled</span>
                                    @endif
                                </div>
                                @if($canChange)
                                <span class="lv-row-actions">
                                    <a href="{{ route('eligibilityEdit', ['id' => $empid, 'eid' => $eli->id]) }}" class="lv-icon-btn" title="Edit" aria-label="Edit">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    @if($isAdmin)
                                        <button type="button" class="lv-icon-btn eligible_approve" value="{{ $eli->id }}" title="Mark as reviewed" aria-label="Mark as reviewed">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        @if ($eli->status == 0)
                                        <button type="button" class="lv-icon-btn" data-toggle="modal" data-target="#eligible-modal" onclick="openEligibilityModal({{ $eli->id }})" title="Cancel with remarks" aria-label="Cancel with remarks">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                        @endif
                                    @endif
                                    <button type="button" class="lv-icon-btn is-danger eligible_delete" value="{{ $eli->id }}" title="Delete" aria-label="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </span>
                                @endif
                            </div>

                            <dl class="eli-details">
                                @foreach($details as [$label, $value])
                                    <div>
                                        <dt>{{ $label }}</dt>
                                        <dd class="{{ $value ? '' : 'lv-muted' }}">{{ $value ?: 'N/A' }}</dd>
                                    </div>
                                @endforeach
                            </dl>

                            @if($eli->status != 0 && $eli->status != 1)
                                <div class="eli-remarks"><b>Remarks:</b> {{ $eli->remarks ?: '—' }}</div>
                            @endif

                            <div class="eli-item-foot">
                                @if($eli->attachment)
                                    <a href="#" class="lv-btn" data-toggle="modal" data-target="#pdfModal"
                                       data-label="{{ $eli->careereligible }}"
                                       data-pdf="{{ asset('storage/' . $eli->attachment) }}" onclick="showPdfModal(this)">
                                        <i class="fas fa-file-pdf"></i> View certificate
                                    </a>
                                @else
                                    <span class="lv-muted" style="font-size: 12.5px;"><i class="fas fa-paperclip mr-1"></i>No certificate attached</span>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="dtr-empty py-4">
                            <div class="dtr-empty-icon"><i class="fas fa-certificate"></i></div>
                            <h6>No eligibilities yet</h6>
                            <p>Use the form above to add one, with its certificate as a PDF.</p>
                        </div>
                    @endforelse
                    <div class="dtr-empty py-4 d-none" id="eliNoMatch">
                        <h6>No matches</h6>
                        <p>Nothing here matches that search.</p>
                    </div>
                </div>
            </div>
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
                <iframe id="modalPdf" src="" title="Certificate" style="display: block; width: 100%; height: 75vh; border: 0;"></iframe>
            </div>
        </div>
    </div>
</div>

<div class="modal fade ev-modal" id="eligible-modal" tabindex="-1" aria-labelledby="eligibleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('eliCancel') }}" class="dtr-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="eligibleModalLabel"><i class="fas fa-ban"></i>Cancel eligibility</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body pb-3">
                    <input type="hidden" name="id" id="eli-id">
                    <label class="dtr-label" for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-control" rows="3" style="height: auto;" placeholder="Why is this eligibility being canceled?" required></textarea>
                    <p class="pds-hint mt-2 mb-0">The employee sees these remarks next to the eligibility.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="lv-btn" data-dismiss="modal">Close</button>
                    <button type="submit" class="lv-btn is-danger"><i class="fas fa-ban"></i> Cancel eligibility</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    // The modal itself opens through data-toggle; this only fills it in.
    function showPdfModal(link) {
        document.getElementById('pdfModalLabel').innerText = link.getAttribute('data-label');
        document.getElementById('modalPdf').src = link.getAttribute('data-pdf');
    }

    function openEligibilityModal(id) {
        document.getElementById('eli-id').value = id;
        document.getElementById('remarks').value = '';
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Stop the PDF however the modal is closed.
        $('#pdfModal').on('hidden.bs.modal', function () {
            document.getElementById('modalPdf').src = '';
        });
        $('#eligible-modal').on('shown.bs.modal', function () {
            document.getElementById('remarks').focus();
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
