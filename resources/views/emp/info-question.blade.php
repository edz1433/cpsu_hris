@extends('layouts.master')

@section('body')
@php
    // Answers and details are stored as comma-separated lists; each input saves its own slot (data-array).
    $question = explode(',', $infoquestion->question);
    $qdetails = explode(',', $infoquestion->qdetails);

    // [index, question, details label, extra lead-in shown above it]
    $relatedLead = 'Are you related by consanguinity or affinity to the appointing or recommending authority, or to the chief of bureau or office or to the person who has immediate supervision over you in the Office,';
    $sectionA = [
        [0, 'Within the third degree?', null, $relatedLead],
        [1, 'Within the fourth degree (for Local Government Unit - Career Employees)?', 'If yes, give details', null],
        [2, 'Have you ever been found guilty of any administrative offense?', 'If yes, give details', null],
        [3, 'Have you been criminally charged before any court?', 'case', null],
        [4, 'Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?', 'If yes, give details', null],
        [5, 'Have you ever been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out (abolition) in the public or private sector?', 'If yes, give details', null],
        [6, 'Have you ever been a candidate in a national or local election held within the last year (except Barangay election)?', 'If yes, give details', null],
        [7, 'Have you resigned from the government service during the three (3)-month period before the last election to promote/actively campaign for a national or local candidate?', 'If yes, give details', null],
        [8, 'Have you acquired the status of an immigrant or permanent resident of another country?', 'If yes, give details (country)', null],
    ];
    $sectionB = [
        [9, 'Are you a member of any indigenous group?', 'If yes, please specify', null],
        [10, 'Are you a person with disability?', 'If yes, please specify (ID no.)', null],
        [11, 'Are you a solo parent?', 'If yes, please specify (ID no.)', null],
    ];
    $sections = [
        ['A', 'fa-balance-scale', 'Relationships, cases and service record', null, $sectionA],
        ['B', 'fa-hands-helping', 'Indigenous peoples, PWD and solo parents', "Pursuant to: (a) Indigenous People's Act (RA 8371); (b) Magna Carta for Disabled Persons (RA 7277); and (c) Solo Parents Welfare Act of 2000 (RA 8972), please answer the following items:", $sectionB],
    ];
@endphp
<div class="container-fluid dash">
    @include('emp.partials.pds-head', ['pdsTitle' => 'Other Information Questions', 'saveUrls' => [route('update.info.question')]])

    <div class="row">
        @include('emp.submenu-side')
        <div class="col-lg-9 dtr-form pds-form">
            @foreach($sections as [$letter, $icon, $title, $intro, $items])
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5><i class="fas {{ $icon }}" style="color: var(--cpsu-green-600);"></i>{{ $letter }}. {{ $title }}</h5>
                    <span class="dash-card-hint iq-progress" data-section="{{ $letter }}"></span>
                </div>
                <div class="dash-card-body" data-section-body="{{ $letter }}">
                    @if($intro)
                        <p class="iq-lead">{{ $intro }}</p>
                    @endif
                    @foreach($items as [$i, $text, $detailsLabel, $lead])
                        @if($lead)
                            <p class="iq-lead">{{ $lead }}</p>
                        @endif
                        <div class="iq-item {{ $lead || ($i == 1) ? 'is-sub' : '' }}">
                            <div class="iq-row">
                                <p class="iq-question"><span class="iq-num">{{ $loop->iteration }}</span>{{ $text }}</p>
                                <div class="dtr-seg iq-answer" role="radiogroup" aria-label="{{ $text }}">
                                    <input class="updated-data" type="radio" name="question_{{ $i }}" data-array="{{ $i }}" id="no-{{ $i }}" value="0" {{ (($question[$i] ?? '') == 0) ? 'checked' : '' }}>
                                    <label for="no-{{ $i }}">No</label>
                                    <input class="updated-data" type="radio" name="question_{{ $i }}" data-array="{{ $i }}" id="yes-{{ $i }}" value="1" {{ (($question[$i] ?? '') == 1) ? 'checked' : '' }}>
                                    <label for="yes-{{ $i }}">Yes</label>
                                </div>
                            </div>
                            {{-- infoquestionScript shows/hides the parent of #details-N with the answer. --}}
                            @if($detailsLabel === null)
                                <div class="d-none">
                                    <input class="input-details updated-data" type="hidden" name="qdetails_{{ $i }}" data-array="{{ $i }}" value="{{ $qdetails[$i] ?? '' }}" id="details-{{ $i }}">
                                </div>
                            @elseif($detailsLabel === 'case')
                                <div class="iq-details is-split">
                                    <label class="dtr-label" for="details-{{ $i }}">Date filed</label>
                                    <input class="form-control input-details updated-data" type="date" name="qdetails_{{ $i }}" data-array="12" value="{{ $qdetails[12] ?? '' }}" id="details-{{ $i }}">
                                    <label class="dtr-label" for="details-{{ $i }}-status">Status of case/s</label>
                                    <input class="form-control input-details updated-data" type="text" name="qdetails_{{ $i }}" data-array="{{ $i }}" value="{{ $qdetails[$i] ?? '' }}" id="details-{{ $i }}-status" placeholder="e.g. Pending, Dismissed" autocomplete="off">
                                </div>
                            @else
                                <div class="iq-details">
                                    <label class="dtr-label" for="details-{{ $i }}">{{ $detailsLabel }}</label>
                                    <input class="form-control input-details updated-data" type="text" name="qdetails_{{ $i }}" data-array="{{ $i }}" value="{{ $qdetails[$i] ?? '' }}" id="details-{{ $i }}" autocomplete="off">
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endforeach
            <p class="pds-hint">Details show up when you answer Yes. Commas are removed from the details because they separate the saved answers.</p>
        </div>
    </div>
</div>
<script>
    // "n of n answered" in each section header.
    document.addEventListener('DOMContentLoaded', function () {
        function refresh() {
            document.querySelectorAll('.iq-progress').forEach(function (hint) {
                var body = document.querySelector('[data-section-body="' + hint.dataset.section + '"]');
                var total = body.querySelectorAll('.iq-answer').length;
                var answered = body.querySelectorAll('.iq-answer input:checked').length;
                hint.textContent = answered + ' of ' + total + ' answered';
                hint.classList.toggle('is-complete', answered === total);
            });
        }
        document.querySelectorAll('.iq-answer input').forEach(function (radio) {
            radio.addEventListener('change', refresh);
        });
        refresh();
    });
</script>
@endsection
