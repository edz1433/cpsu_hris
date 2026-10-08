@extends('layouts.master')

@section('body')
@php
    $isEdit = isset($voluntaryworksedit);
    $isAdmin = $guard == "web";
    $field = fn ($name) => old($name, $isEdit ? $voluntaryworksedit->$name : '');
    $listUrl = $isAdmin ? route('voluntary-work', $employee->id) : route('voluntary-work');
    // Collapsed when there is already a list to look at and nothing is being edited.
    $formOpen = $isEdit || count($voluntaryworks) == 0 || $errors->any();

    $fmtDate = function ($value) {
        if (empty($value)) return null;
        try { return \Carbon\Carbon::parse($value)->format('M d, Y'); } catch (\Exception $e) { return $value; }
    };
@endphp
<div class="container-fluid dash">
    @include('emp.partials.pds-head', [
        'pdsTitle' => 'Voluntary Work',
        'saveUrls' => [],
        'pdsNote' => 'involvement in civic, non-government, people or voluntary organizations.',
    ])

    <div class="row">
        @include('emp.submenu-side')
        <div class="col-lg-9">
            <div class="dash-card {{ $isEdit ? 'um-editing' : '' }}">
                <div class="dash-card-header">
                    <h5>
                        <i class="fas {{ $isEdit ? 'fa-pen' : 'fa-plus-circle' }}" style="color: var(--cpsu-green-600);"></i>
                        {{ $isEdit ? 'Edit voluntary work' : 'Add voluntary work' }}
                    </h5>
                    @if($isEdit)
                        <a href="{{ $listUrl }}" class="dash-card-hint">Cancel</a>
                    @else
                        <button type="button" class="lv-btn {{ $formOpen ? '' : 'is-primary' }}" data-toggle="collapse" data-target="#vworkForm" aria-expanded="{{ $formOpen ? 'true' : 'false' }}" aria-controls="vworkForm" id="vworkFormToggle">
                            <i class="fas {{ $formOpen ? 'fa-chevron-up' : 'fa-plus' }}"></i> <span>{{ $formOpen ? 'Hide form' : 'Add entry' }}</span>
                        </button>
                    @endif
                </div>
                <div class="collapse {{ $formOpen ? 'show' : '' }}" id="vworkForm">
                <div class="dash-card-body">
                    <form class="dtr-form pds-form" action="{{ $isEdit ? route('voluntaryworksUpdate', $voluntaryworksedit->id) : route('voluntaryworksCreate') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if($isEdit)
                            <input type="hidden" name="id" value="{{ $voluntaryworksedit->id }}">
                        @endif
                        <input type="hidden" name="empid" value="{{ $employee->emp_ID }}">

                        <div class="form-row">
                            <div class="col-12 dtr-field">
                                <label class="dtr-label" for="org_name">Name and address of organization <span class="font-weight-normal">(write in full)</span></label>
                                <input type="text" name="org_name" id="org_name" class="form-control @error('org_name') is-invalid @enderror" value="{{ $field('org_name') }}" placeholder="e.g. Philippine Red Cross, Bacolod City Chapter" autocomplete="off" required>
                            </div>
                            <div class="col-md-8 dtr-field">
                                <label class="dtr-label" for="position">Position / nature of work</label>
                                <input type="text" name="position" id="position" class="form-control @error('position') is-invalid @enderror" value="{{ $field('position') }}" placeholder="e.g. Volunteer, blood donation drive" autocomplete="off" required>
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="num_hours">Number of hours</label>
                                <input type="number" name="num_hours" id="num_hours" min="0" class="form-control @error('num_hours') is-invalid @enderror" value="{{ $field('num_hours') }}" placeholder="0" autocomplete="off" required>
                            </div>
                            <div class="col-md-4 col-6 dtr-field">
                                <label class="dtr-label" for="inc_date1">From</label>
                                <input type="date" id="inc_date1" name="inc_date1" class="form-control @error('inc_date1') is-invalid @enderror" value="{{ $field('inc_date1') }}" required>
                            </div>
                            <div class="col-md-4 col-6 dtr-field">
                                <label class="dtr-label" for="inc_date2">To</label>
                                <input type="date" id="inc_date2" name="inc_date2" class="form-control @error('inc_date2') is-invalid @enderror" value="{{ $field('inc_date2') }}" required>
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="attachment">Supporting document <span class="font-weight-normal">(PDF, optional)</span></label>
                                <label class="eli-file @error('attachment') is-invalid @enderror" for="attachment" style="height: 38px;">
                                    <i class="fas fa-file-upload"></i>
                                    <span id="attachmentName">{{ $isEdit && $voluntaryworksedit->attachment ? 'Replace current file' : 'Choose a PDF' }}</span>
                                    <span class="eli-file-btn">Browse</span>
                                </label>
                                <input type="file" name="attachment" id="attachment" class="eli-file-input" accept="application/pdf">
                            </div>
                        </div>
                        @if($isEdit && $voluntaryworksedit->attachment)
                            <p class="pds-hint mt-n2 mb-3 text-md-right">Leave the document empty to keep the current file.</p>
                        @endif

                        <div class="d-flex justify-content-end align-items-center" style="gap: 8px;">
                            @if($isEdit)
                                <a href="{{ $listUrl }}" class="lv-btn">Cancel</a>
                            @endif
                            <button type="submit" name="btn-submit" class="dtr-generate">
                                <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Save changes' : 'Add voluntary work' }}
                            </button>
                        </div>
                    </form>
                </div>
                </div>
            </div>

            <div class="dash-card">
                <div class="dash-card-header flex-wrap">
                    <h5><i class="fas fa-hand-holding-heart" style="color: var(--cpsu-green-600);"></i>Voluntary work <span class="eli-count">{{ count($voluntaryworks) }}</span></h5>
                    @if(count($voluntaryworks) > 0)
                        <label class="emp-search mb-0" for="vworkSearch">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input type="search" name="table_search" id="vworkSearch" placeholder="Search organizations, roles..." autocomplete="off" aria-label="Search voluntary work">
                        </label>
                    @endif
                </div>
                <div class="dash-card-body">
                    @forelse($voluntaryworks as $vwork)
                        <article class="eli-item voluntaryworks-row row-{{ $vwork->id }} {{ $isEdit && $voluntaryworksedit->id == $vwork->id ? 'is-editing' : '' }}">
                            <div class="eli-item-head">
                                <div style="min-width: 0;">
                                    <h6 class="eli-title mb-0">{{ $vwork->position ?: 'Untitled' }}</h6>
                                    <div class="wx-dept">{{ $vwork->org_name }}</div>
                                    <div class="wx-when">
                                        <i class="far fa-calendar-alt"></i>
                                        {{ $fmtDate($vwork->inc_date1) }} &ndash; {{ $fmtDate($vwork->inc_date2) }}
                                        @if($vwork->num_hours)
                                            <span class="wx-dur">&middot; <i class="far fa-clock"></i> {{ number_format($vwork->num_hours) }} {{ $vwork->num_hours == 1 ? 'hour' : 'hours' }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex flex-column align-items-end" style="gap: 8px;">
                                    @if($isAdmin || ($guard == "employee" && $vwork->status == 0))
                                    <span class="lv-row-actions">
                                        <a href="{{ route('voluntaryworksEdit', ['id' => $empid, 'eid' => $vwork->id]) }}" class="lv-icon-btn" title="Edit" aria-label="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        @if($isAdmin)
                                            <button type="button" class="lv-icon-btn voluntaryworks_approve" value="{{ $vwork->id }}" title="Mark as reviewed" aria-label="Mark as reviewed">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            @if ($vwork->status == 0)
                                                <button type="button" class="lv-icon-btn" data-toggle="modal" data-target="#vwork-modal" onclick="openvworkModal({{ $vwork->id }})" title="Cancel with remarks" aria-label="Cancel with remarks">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            @endif
                                        @endif
                                        <button type="button" class="lv-icon-btn is-danger voluntaryworks_delete" value="{{ $vwork->id }}" title="Delete" aria-label="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </span>
                                    @endif
                                    @if ($vwork->status == 0)
                                        <span class="lv-pill is-start" id="status-{{ $vwork->id }}">To be reviewed</span>
                                    @elseif($vwork->status == 1)
                                        <span class="lv-pill is-added">Reviewed</span>
                                    @else
                                        <span class="lv-pill is-deducted">Canceled</span>
                                    @endif
                                </div>
                            </div>

                            @if($vwork->status != 0 && $vwork->status != 1)
                                <div class="eli-remarks"><b>Remarks:</b> {{ $vwork->remarks ?: '—' }}</div>
                            @endif

                            @if(!empty($vwork->attachment))
                                <div class="eli-item-foot">
                                    <a href="#" class="lv-btn" data-toggle="modal" data-target="#pdfModal"
                                       data-label="{{ $vwork->org_name }}"
                                       data-pdf="{{ asset('storage/' . $vwork->attachment) }}" onclick="showPdfModal(this)">
                                        <i class="fas fa-file-pdf"></i> View document
                                    </a>
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="dtr-empty py-4">
                            <div class="dtr-empty-icon"><i class="fas fa-hand-holding-heart"></i></div>
                            <h6>No voluntary work yet</h6>
                            <p>Add civic or volunteer involvement with the form above. Leave this empty if there is none.</p>
                        </div>
                    @endforelse
                    <div class="dtr-empty py-4 d-none" id="vworkNoMatch">
                        <h6>No matches</h6>
                        <p>Nothing here matches that search.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade ev-modal" id="vwork-modal" tabindex="-1" aria-labelledby="vworkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('voluntaryworksCancel') }}" class="dtr-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="vworkModalLabel"><i class="fas fa-ban"></i>Cancel voluntary work</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body pb-3">
                    <input type="hidden" name="id" id="vwork-id">
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

    function openvworkModal(id) {
        document.getElementById('vwork-id').value = id;
        document.getElementById('remarks').value = '';
    }

    document.addEventListener('DOMContentLoaded', function () {
        $('#pdfModal').on('hidden.bs.modal', function () {
            document.getElementById('modalPdf').src = '';
        });
        $('#vwork-modal').on('shown.bs.modal', function () {
            document.getElementById('remarks').focus();
        });

        // Toggle button label follows the form's open/closed state.
        $('#vworkForm').on('shown.bs.collapse hidden.bs.collapse', function (e) {
            var open = e.type === 'shown';
            var btn = document.getElementById('vworkFormToggle');
            if (!btn) return;
            btn.classList.toggle('is-primary', !open);
            btn.querySelector('i').className = 'fas ' + (open ? 'fa-chevron-up' : 'fa-plus');
            btn.querySelector('span').textContent = open ? 'Hide form' : 'Add entry';
            if (open) document.getElementById('org_name').focus();
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
