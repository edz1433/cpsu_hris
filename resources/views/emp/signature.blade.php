@extends('layouts.master')

@section('body')
@php
    // The controller falls back to a placeholder image when no signature is on file.
    $hasSignature = str_starts_with((string) $imageData, 'data:');
@endphp
<script>
    // Defined before the preview so its onload can call it. Runs on first load and again
    // whenever the upload script swaps the preview's src.
    function markSignature(img) {
        var uploaded = img.src.indexOf('data:') === 0;
        var state = document.getElementById('signatureState');
        var stage = document.getElementById('signatureStage');
        if (!state || !stage) return;
        stage.classList.toggle('is-empty', !uploaded);
        state.textContent = uploaded ? 'Uploaded' : 'Not uploaded yet';
        state.className = 'lv-pill ' + (uploaded ? 'is-added' : 'is-start');
    }
</script>
<div class="container-fluid dash">
    @include('emp.partials.pds-head', [
        'pdsTitle' => 'E-Signature',
        'saveUrls' => [],
        'pdsNote' => 'the signature applied to documents generated in the HRIS.',
    ])

    <div class="row">
        @include('emp.submenu-side')
        <div class="col-lg-9">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5><i class="fas fa-signature" style="color: var(--cpsu-green-600);"></i>Signature on file</h5>
                    <span class="lv-pill {{ $hasSignature ? 'is-added' : 'is-start' }}" id="signatureState">{{ $hasSignature ? 'Uploaded' : 'Not uploaded yet' }}</span>
                </div>
                <div class="dash-card-body">
                    <div class="row">
                        <div class="col-md-7 mb-3 mb-md-0">
                            {{-- signatureScript opens the file picker when the preview is clicked. --}}
                            <div class="sig-stage {{ $hasSignature ? '' : 'is-empty' }}" id="signatureStage" title="Click to upload a new signature">
                                <img id="signature-preview" src="{{ $imageData }}" alt="E-signature" onload="markSignature(this)">
                            </div>
                            <div class="d-flex flex-wrap align-items-center mt-3" style="gap: 10px;">
                                <button type="button" class="dtr-generate" onclick="document.getElementById('signature-file').click()">
                                    <i class="fas fa-upload mr-1"></i> {{ $hasSignature ? 'Replace signature' : 'Upload signature' }}
                                </button>
                                <span class="pds-hint m-0">PNG only &middot; saves as soon as you pick a file</span>
                            </div>
                            <input type="file" id="signature-file" accept="image/png" style="display: none;">
                        </div>
                        <div class="col-md-5">
                            <h6 class="sig-rules-title">Before you upload</h6>
                            <ul class="sig-rules">
                                <li><i class="fas fa-check"></i><span><b>PNG with a transparent background</b>, so it sits cleanly on the printed form.</span></li>
                                <li><i class="fas fa-check"></i><span><b>Crop it tight.</b> The signature should cover the whole image, with no empty margins.</span></li>
                                <li><i class="fas fa-lock"></i><span>Stored encrypted and used on documents such as leave applications and IPCR/DPCR forms.</span></li>
                            </ul>
                            <figure class="sig-example">
                                <img src="{{ asset('Uploads/esign-note.jpg') }}" alt="Correct: the signature fills the whole box. Wrong: a small signature with lots of white space around it.">
                                <figcaption>Left: fills the space. Right: too much white space.</figcaption>
                            </figure>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
