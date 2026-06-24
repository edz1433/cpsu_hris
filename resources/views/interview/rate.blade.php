@extends('layouts.master')

@section('body')
<style>
    .rating-page .card { border:0; border-radius:18px; box-shadow:0 9px 30px rgba(15,23,42,.07); }
    .rating-hero { background:#f8faf9; border:1px solid #dfe7e2; border-radius:18px; padding:18px; }
    .rating-table th { background:#f8fafc; font-size:.78rem; text-transform:uppercase; }
    .rating-table td, .rating-table th { vertical-align:middle; }
    .score-choice { 
        align-items:center; 
        display:inline-flex; 
        gap:2px; 
        margin:0 2px 2px 0; 
    }
    .score-choice input { 
        margin:0; 
        width:16px; 
        height:16px; 
        cursor:pointer; 
    }
    .score-choice span { 
        font-size:.7rem; 
        min-width:16px; 
        text-align:center; 
    }
    .trait-prompt { 
        color:#536171; 
        font-size:.72rem; 
        line-height:1.3; 
        margin-top:3px; 
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .level-grid { 
        display:grid; 
        gap:4px; 
        grid-template-columns:repeat(5,1fr); 
    }
    .level-card { 
        background:#fafbfc; 
        border:1px solid #e5e9f0; 
        border-radius:6px; 
        padding:6px 6px 8px; 
        min-height:60px;
    }
    .level-card strong { 
        color:#1f2937; 
        display:block; 
        font-size:.65rem; 
        margin-bottom:3px; 
        text-align:center;
    }
    .level-card small { 
        color:#5f6b7a; 
        display:block; 
        line-height:1.2; 
        min-height:28px; 
        font-size:.6rem;
        text-align:center;
        margin-bottom:4px;
    }
    .level-card .score-options { 
        display:flex; 
        flex-wrap:wrap; 
        justify-content:center; 
        gap:1px;
    }
    .interview-instructions { 
        background:#f8fafc; 
        border:1px solid #d9e1ea; 
        border-radius:10px; 
        color:#475569; 
        font-size:.8rem; 
        line-height:1.4; 
        padding:10px 12px; 
        margin-bottom:12px;
    }
    .tab-pane { padding-top:12px; }
    .rating-page .nav-tabs .nav-link {
        background:#f8fafc;
        border-color:#d9e1ea #d9e1ea #dee2e6;
        color:#1f2937 !important;
        font-weight:700;
        padding:8px 16px;
        font-size:.85rem;
    }
    .rating-page .nav-tabs .nav-link.active {
        background:#fff;
        border-color:#d9e1ea #d9e1ea #fff;
        color:#166534 !important;
    }
    .rating-page .nav-tabs .nav-link i {
        color:inherit !important;
    }
    .total-box { background:#e8f6ee; border-radius:14px; color:#166534; font-weight:800; padding:12px 16px; text-align:center; }
    .autosave-status { color:#64748b; font-size:.72rem; min-height:18px; }
    .autosave-status.saving { color:#b7791f; }
    .autosave-status.saved { color:#166534; }
    .autosave-status.error { color:#b91c1c; }
    
    /* Compact trait row */
    .trait-row td:first-child { 
        width:28%; 
        padding:8px 10px; 
    }
    .trait-row td:last-child { 
        padding:6px 8px; 
    }
    .trait-row .trait-label { 
        font-size:.78rem; 
        display:block; 
        margin-bottom:2px; 
    }
    
    /* Mobile optimizations */
    @media(max-width:767.98px) {
        .level-grid { 
            grid-template-columns:repeat(3,1fr); 
            gap:3px;
        }
        .level-card { 
            padding:4px 4px 6px; 
            min-height:50px;
        }
        .level-card strong { 
            font-size:.58rem; 
        }
        .level-card small { 
            font-size:.52rem; 
            min-height:20px;
        }
        .score-choice { 
            margin:0 1px 1px 0; 
        }
        .score-choice input { 
            width:14px; 
            height:14px; 
        }
        .score-choice span { 
            font-size:.6rem; 
            min-width:14px; 
        }
        .trait-row td:first-child { 
            width:35%; 
            padding:6px 6px; 
        }
        .trait-row td:last-child { 
            padding:4px 4px; 
        }
        .trait-row .trait-label { 
            font-size:.7rem; 
        }
        .trait-prompt { 
            font-size:.62rem; 
            -webkit-line-clamp:2;
        }
        .interview-instructions { 
            font-size:.7rem; 
            padding:8px 10px; 
        }
        .rating-page .nav-tabs .nav-link {
            padding:6px 10px;
            font-size:.75rem;
        }
        .total-box { 
            padding:8px 12px; 
            font-size:.85rem;
        }
        .total-box small { 
            font-size:.65rem; 
        }
    }
    
    @media(max-width:400px) {
        .level-grid { 
            grid-template-columns:repeat(2,1fr); 
        }
        .trait-row td:first-child { 
            width:40%; 
        }
    }
</style>

@php
    $interviewScores = $rating->interview_scores ?? [];
    $potentialScores = $rating->potential_scores ?? [];
@endphp

<div class="container-fluid rating-page">
    <form method="POST" action="{{ route('interviewRatingSave', [$interview->id, $application->id]) }}" id="interviewRatingForm">
        @csrf
        <input type="hidden" name="panel_employee_id" value="{{ $panelEmployee->id }}">

        <div class="rating-hero mb-3">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <small class="text-muted">Interview Assessment Form</small>
                    <h4 class="font-weight-bold mb-1">{{ trim($application->first_name.' '.$application->middle_name.' '.$application->last_name) }}</h4>
                    {{-- <div class="text-muted small">{{ $application->app_number }}</div> --}}
                    <div class="text-muted small">{{ $interview->job->title ?? 'Position' }}</div>
                    @if($interview->job && $interview->job->plantilla_item_no)
                        <div class="text-muted small">{{ $interview->job->plantilla_item_no }}</div>
                    @endif
                    {{-- <div class="text-muted small">{{ $interview->eteEvaluation->office->office_name ?? '' }}</div> --}}
                </div>
                <div class="col-md-4 text-md-right mt-3 mt-md-0">
                    <div class="text-muted small">Rated by</div>
                    <strong>{{ trim($panelEmployee->fname.' '.$panelEmployee->mname.' '.$panelEmployee->lname) }}</strong>
                    <div>
                        <span class="badge badge-success mt-2" id="ratingSavedBadge" style="{{ $rating->submitted_at ? '' : 'display:none;' }}">
                            Saved {{ optional($rating->submitted_at)->format('M d, Y h:i A') }}
                        </span>
                    </div>
                    <div class="autosave-status mt-1" id="autosaveStatus"></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs" id="interviewRatingTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#interview-tab" role="tab">
                            <i class="fas fa-microphone-alt mr-1"></i> Interview Assessment
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#potential-tab" role="tab">
                            <i class="fas fa-chart-line mr-1"></i> Potential Assessment
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="interview-tab" role="tabpanel">
                        <div class="interview-instructions">
                            <strong>Instructions:</strong> Rate the candidate's physical characteristics and personality traits using a 10-point scale. Select the number that corresponds to your rating for each item.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered rating-table">
                                <thead>
                                    <tr>
                                        <th style="width:25%;">Traits</th>
                                        <th>Rating Scale</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($interviewCriteria as $key => $criterion)
                                        <tr class="trait-row">
                                            <td>
                                                <span class="trait-label"><strong>{{ $loop->iteration }}.</strong> {{ $criterion['label'] }}</span>
                                                <div class="trait-prompt">{{ $criterion['prompt'] }}</div>
                                            </td>
                                            <td>
                                                <div class="level-grid">
                                                    @foreach($criterion['levels'] as $range => $description)
                                                        @php
                                                            [$start, $end] = array_map('intval', explode(' - ', $range));
                                                        @endphp
                                                        <div class="level-card">
                                                            <small>{{ $description }}</small>
                                                            <div class="score-options">
                                                                @for($score = $start; $score <= $end; $score++)
                                                                    <label class="score-choice">
                                                                        <input type="radio" name="interview_scores[{{ $key }}]" value="{{ $score }}" {{ (int)($interviewScores[$key] ?? 0) === $score ? 'checked' : '' }} required>
                                                                        <span>{{ $score }}</span>
                                                                    </label>
                                                                @endfor
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="potential-tab" role="tabpanel">
                        @foreach($potentialCriteria as $group => $items)
                            <h6 class="font-weight-bold mt-2">{{ $group }}</h6>
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered rating-table">
                                    <thead>
                                        <tr>
                                            <th style="width:55%;">Factor</th>
                                            <th>Rating (5 Excellent, 1 Poor)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $key => $label)
                                            <tr>
                                                <td>{{ $label }}</td>
                                                <td>
                                                    @for($score = 5; $score >= 1; $score--)
                                                        <label class="score-choice">
                                                            <input type="radio" name="potential_scores[{{ $key }}]" value="{{ $score }}" {{ (int)($potentialScores[$key] ?? 0) === $score ? 'checked' : '' }} required>
                                                            <span>{{ $score }}</span>
                                                        </label>
                                                    @endfor
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="row align-items-end mt-3">
                    <div class="col-md-8">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Optional notes">{{ old('remarks', $rating->remarks) }}</textarea>
                    </div>
                    <div class="col-md-4 mt-3 mt-md-0">
                        <div class="total-box">
                            <small class="d-block">Current Total</small>
                            <span id="currentTotalScore">{{ number_format($rating->total_score, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('interviewRatingForm');
    const status = document.getElementById('autosaveStatus');
    const totalScore = document.getElementById('currentTotalScore');
    const savedBadge = document.getElementById('ratingSavedBadge');
    const currentUrl = window.location.href.split('#')[0];
    const fallbackUrl = "{{ auth()->guard('web')->check() ? route('interviewEvaluationShow', $interview->id) : route('interviewAssignments') }}";
    const currentActiveKey = "{{ $interview->id }}:{{ $application->id }}";
    const panelEmployeeId = "{{ $panelEmployee->id }}";
    const interviewKeys = @json(array_keys($interviewCriteria));
    const potentialKeys = @json(collect($potentialCriteria)->flatMap(fn ($items) => array_keys($items))->values());
    let autosaveTimer = null;
    let autosaveRunning = false;
    let autosaveQueued = false;

    function setSaveStatus(text, state) {
        if (!status) return;
        status.textContent = text || '';
        status.className = 'autosave-status mt-1' + (state ? ' ' + state : '');
    }

    function selectedScore(name) {
        const selected = form.querySelector('input[name="' + name + '"]:checked');
        return selected ? parseInt(selected.value || '0', 10) : 0;
    }

    function updateCurrentTotal() {
        const interviewTotal = interviewKeys.reduce(function (sum, key) {
            return sum + selectedScore('interview_scores[' + key + ']');
        }, 0);
        const potentialTotal = potentialKeys.reduce(function (sum, key) {
            return sum + selectedScore('potential_scores[' + key + ']');
        }, 0);

        if (totalScore) {
            totalScore.textContent = (interviewTotal + potentialTotal).toFixed(2);
        }
    }

    function submitRating() {
        if (!form) return;
        if (autosaveRunning) {
            autosaveQueued = true;
            return;
        }

        const formData = new FormData(form);
        formData.append('autosave', '1');

        autosaveRunning = true;
        setSaveStatus('Saving...', 'saving');

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData,
            cache: 'no-store'
        })
            .then(function (response) {
                if (!response.ok) {
                    return response.json().catch(function () {
                        throw new Error('Unable to save rating.');
                    }).then(function (payload) {
                        if (payload.redirect) {
                            window.location.replace(payload.redirect);
                            throw new Error('Redirecting...');
                        }
                        const message = payload.message || 'Please complete the required ratings.';
                        throw new Error(message);
                    });
                }

                return response.json();
            })
            .then(function (data) {
                if (totalScore && data.total_score) {
                    totalScore.textContent = data.total_score;
                }

                if (savedBadge && data.complete) {
                    savedBadge.textContent = 'Saved ' + data.saved_at;
                    savedBadge.style.display = '';
                }

                setSaveStatus(data.complete ? 'Saved automatically.' : 'Draft saved automatically.', 'saved');
            })
            .catch(function (error) {
                setSaveStatus(error.message || 'Save failed. Please try again.', 'error');
            })
            .finally(function () {
                autosaveRunning = false;
                if (autosaveQueued) {
                    autosaveQueued = false;
                    submitRating();
                }
            });
    }

    function scheduleAutosave() {
        updateCurrentTotal();
        window.clearTimeout(autosaveTimer);
        autosaveTimer = window.setTimeout(function () {
            submitRating();
        }, 450);
    }

    if (form) {
        updateCurrentTotal();
        form.querySelectorAll('input[type="radio"]').forEach(function (input) {
            input.addEventListener('change', scheduleAutosave);
        });
        form.querySelectorAll('textarea').forEach(function (textarea) {
            textarea.addEventListener('input', scheduleAutosave);
        });
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            updateCurrentTotal();
            submitRating();
        });
    }

    function checkCurrentCast() {
        fetch("{{ route('interviewRatingStatus', [$interview->id, $application->id]) }}?panel_id=" + encodeURIComponent(panelEmployeeId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store'
        })
            .then(response => response.json())
            .then(data => {
                const nextUrl = data.url || fallbackUrl;
                const activeKey = data.active_key || '';

                if (!data.active || activeKey !== currentActiveKey) {
                    if (nextUrl.split('#')[0] === currentUrl) {
                        window.location.reload();
                        return;
                    }

                    window.location.replace(nextUrl);
                }
            })
            .catch(() => {});
    }

    checkCurrentCast();
    setInterval(checkCurrentCast, 3000);
});
</script>
@endsection
