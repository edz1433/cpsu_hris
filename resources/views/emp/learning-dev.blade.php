@extends('layouts.master')

@section('body')
@php
    $isEdit = isset($learningdevedit);
    $isAdmin = $guard == "web";
    $field = fn ($name) => old($name, $isEdit ? $learningdevedit->$name : '');
    $listUrl = $isAdmin ? route('learning-dev', $employee->id) : route('learning-dev');
    // Collapsed when there is already a list to look at and nothing is being edited.
    $formOpen = $isEdit || count($learningdev) == 0 || $errors->any();
    $totalHours = collect($learningdev)->sum(fn ($l) => (float) $l->num_hours);

    $fmtDate = function ($value) {
        if (empty($value)) return null;
        try { return \Carbon\Carbon::parse($value)->format('M d, Y'); } catch (\Exception $e) { return $value; }
    };
@endphp
<div class="container-fluid dash">
    @include('emp.partials.pds-head', [
        'pdsTitle' => 'Learning and Development',
        'saveUrls' => [],
        'pdsNote' => 'training programs and L&D interventions attended.',
    ])

    <div class="row">
        @include('emp.submenu-side')
        <div class="col-lg-9">
            <div class="dash-card {{ $isEdit ? 'um-editing' : '' }}">
                <div class="dash-card-header">
                    <h5>
                        <i class="fas {{ $isEdit ? 'fa-pen' : 'fa-plus-circle' }}" style="color: var(--cpsu-green-600);"></i>
                        {{ $isEdit ? 'Edit training' : 'Add training' }}
                    </h5>
                    @if($isEdit)
                        <a href="{{ $listUrl }}" class="dash-card-hint">Cancel</a>
                    @else
                        <button type="button" class="lv-btn {{ $formOpen ? '' : 'is-primary' }}" data-toggle="collapse" data-target="#learnForm" aria-expanded="{{ $formOpen ? 'true' : 'false' }}" aria-controls="learnForm" id="learnFormToggle">
                            <i class="fas {{ $formOpen ? 'fa-chevron-up' : 'fa-plus' }}"></i> <span>{{ $formOpen ? 'Hide form' : 'Add training' }}</span>
                        </button>
                    @endif
                </div>
                <div class="collapse {{ $formOpen ? 'show' : '' }}" id="learnForm">
                <div class="dash-card-body">
                    <form class="dtr-form pds-form" action="{{ $isEdit ? route('learningdevUpdate', $learningdevedit->id) : route('learningdevCreate') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if($isEdit)
                            <input type="hidden" name="id" value="{{ $learningdevedit->id }}">
                        @endif
                        <input type="hidden" name="empid" value="{{ $employee->emp_ID }}">

                        <div class="form-row">
                            <div class="col-12 dtr-field">
                                <label class="dtr-label" for="learning_dev">Title of training program / L&amp;D intervention <span class="font-weight-normal">(write in full)</span></label>
                                <input type="text" name="learning_dev" id="learning_dev" class="form-control @error('learning_dev') is-invalid @enderror" value="{{ $field('learning_dev') }}" placeholder="e.g. Seminar-Workshop on Records Management" autocomplete="off" required>
                            </div>
                            <div class="col-md-8 dtr-field">
                                <label class="dtr-label" for="conducted">Conducted / sponsored by</label>
                                <input type="text" name="conducted" id="conducted" class="form-control @error('conducted') is-invalid @enderror" value="{{ $field('conducted') }}" placeholder="e.g. Civil Service Commission" autocomplete="off" required>
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="types">Type of L&amp;D</label>
                                <input type="text" name="types" id="types" list="ldTypes" class="form-control @error('types') is-invalid @enderror" value="{{ $field('types') }}" placeholder="Managerial, Technical..." autocomplete="off" required>
                                <datalist id="ldTypes">
                                    <option value="Managerial">
                                    <option value="Supervisory">
                                    <option value="Technical">
                                    <option value="Foundation">
                                </datalist>
                            </div>
                            <div class="col-md-3 col-6 dtr-field">
                                <label class="dtr-label" for="inc_date1">From</label>
                                <input type="date" id="inc_date1" name="inc_date1" class="form-control @error('inc_date1') is-invalid @enderror" value="{{ $field('inc_date1') }}" required>
                            </div>
                            <div class="col-md-3 col-6 dtr-field">
                                <label class="dtr-label" for="inc_date2">To</label>
                                <input type="date" id="inc_date2" name="inc_date2" class="form-control @error('inc_date2') is-invalid @enderror" value="{{ $field('inc_date2') }}" required>
                            </div>
                            <div class="col-md-2 dtr-field">
                                <label class="dtr-label" for="num_hours">Hours</label>
                                <input type="number" name="num_hours" id="num_hours" min="0" class="form-control @error('num_hours') is-invalid @enderror" value="{{ $field('num_hours') }}" placeholder="0" autocomplete="off" required>
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="attachment">Certificate <span class="font-weight-normal">(PDF, optional)</span></label>
                                <label class="eli-file @error('attachment') is-invalid @enderror" for="attachment" style="height: 38px;">
                                    <i class="fas fa-file-upload"></i>
                                    <span id="attachmentName">{{ $isEdit && $learningdevedit->attachment ? 'Replace current file' : 'Choose a PDF' }}</span>
                                    <span class="eli-file-btn">Browse</span>
                                </label>
                                <input type="file" name="attachment" id="attachment" class="eli-file-input" accept="application/pdf">
                            </div>
                        </div>
                        @if($isEdit && $learningdevedit->attachment)
                            <p class="pds-hint mt-n2 mb-3 text-md-right">Leave the certificate empty to keep the current file.</p>
                        @endif

                        <div class="d-flex justify-content-end align-items-center" style="gap: 8px;">
                            @if($isEdit)
                                <a href="{{ $listUrl }}" class="lv-btn">Cancel</a>
                            @endif
                            <button type="submit" name="btn-submit" class="dtr-generate">
                                <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Save changes' : 'Add training' }}
                            </button>
                        </div>
                    </form>
                </div>
                </div>
            </div>

            <div class="dash-card">
                <div class="dash-card-header flex-wrap">
                    <h5>
                        <i class="fas fa-book" style="color: var(--cpsu-green-600);"></i>Trainings attended <span class="eli-count">{{ count($learningdev) }}</span>
                        @if($totalHours > 0)
                            <span class="dash-card-hint font-weight-normal ml-1">{{ number_format($totalHours) }} hours in total</span>
                        @endif
                    </h5>
                    @if(count($learningdev) > 0)
                        <label class="emp-search mb-0" for="learnSearch">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input type="search" name="table_search" id="learnSearch" placeholder="Search titles, sponsors, types..." autocomplete="off" aria-label="Search trainings">
                        </label>
                    @endif
                </div>
                <div class="dash-card-body">
                    @forelse($learningdev as $learning)
                        <article class="eli-item learningdev-row row-{{ $learning->id }} {{ $isEdit && $learningdevedit->id == $learning->id ? 'is-editing' : '' }}">
                            <div class="eli-item-head">
                                <div style="min-width: 0;">
                                    <h6 class="eli-title mb-0">{{ $learning->learning_dev ?: 'Untitled training' }}</h6>
                                    <div class="wx-dept">{{ $learning->conducted }}</div>
                                    <div class="wx-when">
                                        <i class="far fa-calendar-alt"></i>
                                        {{ $fmtDate($learning->inc_date1) }} &ndash; {{ $fmtDate($learning->inc_date2) }}
                                        @if($learning->num_hours)
                                            <span class="wx-dur">&middot; <i class="far fa-clock"></i> {{ number_format($learning->num_hours) }} {{ $learning->num_hours == 1 ? 'hour' : 'hours' }}</span>
                                        @endif
                                        @if($learning->types)
                                            <span class="um-page ml-1 mb-0">{{ $learning->types }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex flex-column align-items-end" style="gap: 8px;">
                                    @if($isAdmin || ($guard == "employee" && in_array($learning->status, [0, 2])))
                                    <span class="lv-row-actions">
                                        <a href="{{ route('learningdevEdit', ['id' => $empid, 'eid' => $learning->id]) }}" class="lv-icon-btn" title="Edit" aria-label="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        @if($isAdmin)
                                            <button type="button" class="lv-icon-btn learningdev_approve" value="{{ $learning->id }}" title="Mark as reviewed" aria-label="Mark as reviewed">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            @if ($learning->status == 0)
                                                <button type="button" class="lv-icon-btn" data-toggle="modal" data-target="#learndev-modal" onclick="openlearndevModal({{ $learning->id }})" title="Cancel with remarks" aria-label="Cancel with remarks">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            @endif
                                        @endif
                                        <button type="button" class="lv-icon-btn is-danger learningdev_delete" value="{{ $learning->id }}" title="Delete" aria-label="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </span>
                                    @endif
                                    @if ($learning->status == 0)
                                        <span class="lv-pill is-start" id="status-{{ $learning->id }}">To be reviewed</span>
                                    @elseif($learning->status == 1)
                                        <span class="lv-pill is-added">Reviewed</span>
                                    @else
                                        <span class="lv-pill is-deducted">Canceled</span>
                                    @endif
                                </div>
                            </div>

                            @if($learning->status != 0 && $learning->status != 1)
                                <div class="eli-remarks"><b>Remarks:</b> {{ $learning->remarks ?: '—' }}</div>
                            @endif

                            @if(!empty($learning->attachment))
                                <div class="eli-item-foot">
                                    <a href="#" class="lv-btn" data-toggle="modal" data-target="#pdfModal"
                                       data-label="{{ $learning->learning_dev }}"
                                       data-pdf="{{ asset('storage/' . $learning->attachment) }}" onclick="showPdfModal(this)">
                                        <i class="fas fa-file-pdf"></i> View certificate
                                    </a>
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="dtr-empty py-4">
                            <div class="dtr-empty-icon"><i class="fas fa-book"></i></div>
                            <h6>No trainings yet</h6>
                            <p>Add each training program attended with the form above.</p>
                        </div>
                    @endforelse
                    <div class="dtr-empty py-4 d-none" id="learnNoMatch">
                        <h6>No matches</h6>
                        <p>Nothing here matches that search.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade ev-modal" id="learndev-modal" tabindex="-1" aria-labelledby="learndevModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('learningdevCancel') }}" class="dtr-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="learndevModalLabel"><i class="fas fa-ban"></i>Cancel training</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body pb-3">
                    <input type="hidden" name="id" id="learndev-id">
                    <label class="dtr-label" for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" class="form-control" rows="3" style="height: auto;" placeholder="Why is this entry being canceled?" required></textarea>
                    <p class="pds-hint mt-2 mb-0">The employee sees these remarks next to the entry and can fix and resubmit it.</p>
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
                <iframe id="modalPdf" src="" title="Certificate" style="display: block; width: 100%; height: 75vh; border: 0;"></iframe>
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

    function openlearndevModal(id) {
        document.getElementById('learndev-id').value = id;
        document.getElementById('remarks').value = '';
    }

    document.addEventListener('DOMContentLoaded', function () {
        $('#pdfModal').on('hidden.bs.modal', function () {
            document.getElementById('modalPdf').src = '';
        });
        $('#learndev-modal').on('shown.bs.modal', function () {
            document.getElementById('remarks').focus();
        });

        // Toggle button label follows the form's open/closed state.
        $('#learnForm').on('shown.bs.collapse hidden.bs.collapse', function (e) {
            var open = e.type === 'shown';
            var btn = document.getElementById('learnFormToggle');
            if (!btn) return;
            btn.classList.toggle('is-primary', !open);
            btn.querySelector('i').className = 'fas ' + (open ? 'fa-chevron-up' : 'fa-plus');
            btn.querySelector('span').textContent = open ? 'Hide form' : 'Add training';
            if (open) document.getElementById('learning_dev').focus();
        });

        var fileInput = document.getElementById('attachment');
        fileInput.addEventListener('change', function () {
            var name = fileInput.files.length ? fileInput.files[0].name : 'Choose a PDF';
            document.getElementById('attachmentName').textContent = name;
            fileInput.previousElementSibling.classList.toggle('has-file', fileInput.files.length > 0);
        });
    });
</script>
@endsection
