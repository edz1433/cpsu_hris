@extends('layouts.master')

@section('body')
@php
    $current_route = request()->route()->getName();
    $isEdit = $current_route == 'jEdit';
    // Field value: what was just typed (after a failed save), else the job being edited.
    $val = fn ($field, $default = '') => old($field, $isEdit ? $jEdit->{$field} : $default);
    $jobTypes = [1 => 'Non-Teaching', 2 => 'Teaching'];
    // Newest postings first.
    $jobs = $jobs->sortBy([['posted_at', 'desc'], ['id', 'desc']])->values();
    $openCount = $jobs->where('status', 'Open')->count();
    $qualifications = [
        'education'   => 'Education',
        'eligibility' => 'Eligibility',
        'training'    => 'Training',
        'experience'  => 'Experience',
        'competency'  => 'Competency',
    ];
    $blank = fn ($text) => trim((string) $text) === '' || in_array(strtolower(trim($text)), ['n/a', 'na', 'none', '-']);
@endphp
<div class="container-fluid dash">
    <div class="dtr-head">
        <div>
            <h1>Career</h1>
            <p>Job postings for plantilla positions, with their qualification standards and posting window.</p>
        </div>
        @if($isEdit)
            <a href="{{ route('jlist') }}" class="lv-btn"><i class="fas fa-plus"></i> New posting</a>
        @endif
    </div>

    <div class="row">
        <div class="col-xl-4 col-lg-5">
            <div class="dash-card job-form-card {{ $isEdit ? 'um-editing' : '' }}">
                <div class="dash-card-header">
                    <h5>
                        <i class="fas {{ $isEdit ? 'fa-pen' : 'fa-plus-circle' }}" style="color: var(--cpsu-green-600);"></i>
                        {{ $isEdit ? 'Edit posting' : 'New posting' }}
                    </h5>
                    @if($isEdit)
                        <a href="{{ route('jlist') }}" class="dash-card-hint">Cancel</a>
                    @endif
                </div>
                <div class="dash-card-body">
                    <form class="dtr-form" action="{{ $isEdit ? route('jUpdate') : route('jCreate') }}" method="POST">
                        @csrf
                        <input type="hidden" name="id" value="{{ $isEdit ? $jEdit->id : '' }}">

                        <div class="job-form-group">Position</div>

                        <div class="dtr-field">
                            <label class="dtr-label" for="jobTitle">Position title</label>
                            <input type="text" name="title" id="jobTitle" value="{{ $val('title') }}" placeholder="e.g. Administrative Officer IV" class="form-control @error('title') is-invalid @enderror" autocomplete="off" required>
                        </div>

                        <div class="form-row">
                            <div class="col-sm-6 dtr-field">
                                <label class="dtr-label" for="jobType">Type</label>
                                <select name="type" id="jobType" class="form-control @error('type') is-invalid @enderror" required>
                                    <option value="">Select type</option>
                                    @foreach($jobTypes as $typeVal => $typeLabel)
                                        <option value="{{ $typeVal }}" {{ (string) $val('type') === (string) $typeVal ? 'selected' : '' }}>{{ $typeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6 dtr-field">
                                <label class="dtr-label" for="jobSalary">Monthly salary</label>
                                <div class="job-peso">
                                    <span aria-hidden="true">₱</span>
                                    <input type="number" step="0.01" min="0" name="salary" id="jobSalary" value="{{ $val('salary') }}" placeholder="0.00" class="form-control @error('salary') is-invalid @enderror" required>
                                </div>
                            </div>
                        </div>

                        <div class="dtr-field">
                            <label class="dtr-label" for="jobPlantilla">Plantilla item no.</label>
                            <input type="text" name="plantilla_item_no" id="jobPlantilla" value="{{ $val('plantilla_item_no') }}" placeholder="e.g. CPSUB-ADOF4-12-2025" class="form-control @error('plantilla_item_no') is-invalid @enderror" autocomplete="off" required>
                            @error('plantilla_item_no')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>

                        <div class="dtr-field">
                            <label class="dtr-label" for="jobAssignment">Place of assignment <span class="font-weight-normal">(optional)</span></label>
                            <textarea name="assignment" id="jobAssignment" rows="2" class="form-control" placeholder="e.g. CPSU Main Campus, Research Unit">{{ $val('assignment') }}</textarea>
                        </div>

                        <div class="job-form-group">Qualification standards</div>

                        <div class="dtr-field">
                            <label class="dtr-label" for="jobEducation">Education</label>
                            <textarea name="education" id="jobEducation" rows="2" class="form-control @error('education') is-invalid @enderror" placeholder="e.g. Bachelor's degree relevant to the job" required>{{ $val('education') }}</textarea>
                        </div>

                        <div class="dtr-field">
                            <label class="dtr-label" for="jobEligibility">Eligibility</label>
                            <textarea name="eligibility" id="jobEligibility" rows="2" class="form-control @error('eligibility') is-invalid @enderror" placeholder="e.g. Career Service (Professional) Second Level Eligibility" required>{{ $val('eligibility') }}</textarea>
                        </div>

                        <div class="form-row">
                            <div class="col-sm-6 dtr-field">
                                <label class="dtr-label" for="jobTraining">Training <span class="font-weight-normal">(optional)</span></label>
                                <textarea name="training" id="jobTraining" rows="2" class="form-control" placeholder="e.g. 8 hours of relevant training">{{ $val('training') }}</textarea>
                            </div>
                            <div class="col-sm-6 dtr-field">
                                <label class="dtr-label" for="jobExperience">Experience <span class="font-weight-normal">(optional)</span></label>
                                <textarea name="experience" id="jobExperience" rows="2" class="form-control" placeholder="e.g. 2 years of relevant experience">{{ $val('experience') }}</textarea>
                            </div>
                        </div>

                        <div class="dtr-field">
                            <label class="dtr-label" for="jobCompetency">Competency <span class="font-weight-normal">(optional)</span></label>
                            <textarea name="competency" id="jobCompetency" rows="3" class="form-control" placeholder="Preferred skills, other requirements">{{ $val('competency') }}</textarea>
                        </div>

                        <div class="job-form-group">Posting</div>

                        <div class="form-row">
                            <div class="col-sm-6 dtr-field">
                                <label class="dtr-label" for="jobPosted">Posted on</label>
                                <input type="date" name="posted_at" id="jobPosted" value="{{ $val('posted_at') }}" class="form-control @error('posted_at') is-invalid @enderror" required>
                            </div>
                            <div class="col-sm-6 dtr-field">
                                <label class="dtr-label" for="jobExpiration">Closes on</label>
                                <input type="date" name="expiration_at" id="jobExpiration" value="{{ $val('expiration_at') }}" class="form-control @error('expiration_at') is-invalid @enderror" required>
                            </div>
                        </div>

                        <div class="dtr-field">
                            <span class="dtr-label" id="jobStatusLabel">Status</span>
                            <div class="dtr-seg is-block" role="radiogroup" aria-labelledby="jobStatusLabel">
                                @foreach(['Open', 'Closed'] as $statusOpt)
                                    <input type="radio" name="status" id="jobStatus{{ $statusOpt }}" value="{{ $statusOpt }}" {{ $val('status', 'Open') == $statusOpt ? 'checked' : '' }} required>
                                    <label for="jobStatus{{ $statusOpt }}">{{ $statusOpt }}</label>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit" class="dtr-generate w-100">
                            <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Save changes' : 'Post job' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5><i class="fas fa-briefcase" style="color: var(--cpsu-green-600);"></i>Job postings</h5>
                    <span class="dash-card-hint">
                        <span class="lv-pill is-added">{{ $openCount }} open</span>
                        {{ count($jobs) }} total
                    </span>
                </div>
                <div class="dash-card-body">
                    <div class="table-responsive">
                        <table id="example1" class="table table-hover lv-table job-table">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Position</th>
                                    <th>Salary</th>
                                    <th>Posting window</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tbody">
                                @foreach($jobs as $job)
                                    @php
                                        $posted = \Carbon\Carbon::parse($job->posted_at);
                                        $closes = \Carbon\Carbon::parse($job->expiration_at);
                                        $isOpen = $job->status == 'Open';
                                        $daysLeft = (int) now()->startOfDay()->diffInDays($closes->copy()->startOfDay(), false);
                                        $assignment = preg_replace('/\s*\R\s*/', ' · ', trim((string) $job->assignment));
                                    @endphp
                                    <tr id="tr-{{ $job->id }}" class="{{ $isEdit && $jEdit->id == $job->id ? 'um-row-active' : '' }}">
                                        <td class="text-center lv-muted lv-num">{{ $loop->iteration }}</td>
                                        <td class="job-pos">
                                            <div class="um-name">{{ $job->title }}</div>
                                            <div class="job-meta">
                                                <span class="lv-pill {{ $job->type == 2 ? 'is-info' : 'job-nonteach' }}">{{ $jobTypes[$job->type] ?? 'Other' }}</span>
                                                @if($job->plantilla_item_no)
                                                    <span class="job-plantilla" title="Plantilla item no."><i class="fas fa-hashtag"></i>{{ $job->plantilla_item_no }}</span>
                                                @endif
                                            </div>
                                            @if($assignment !== '')
                                                <div class="job-assign"><i class="fas fa-map-marker-alt"></i>{{ $assignment }}</div>
                                            @endif
                                            <details class="job-qs">
                                                <summary>Qualification standards</summary>
                                                <dl>
                                                    @foreach($qualifications as $field => $label)
                                                        <dt>{{ $label }}</dt>
                                                        <dd class="{{ $blank($job->{$field}) ? 'lv-muted' : '' }}">{!! $blank($job->{$field}) ? '—' : nl2br(e(trim($job->{$field}))) !!}</dd>
                                                    @endforeach
                                                </dl>
                                            </details>
                                        </td>
                                        <td class="job-salary" data-order="{{ $job->salary }}">₱{{ number_format($job->salary, 2) }}</td>
                                        <td class="job-window" data-order="{{ $posted->format('Y-m-d') }}">
                                            <div>{{ $posted->format('M d, Y') }}</div>
                                            <div class="lv-muted">to {{ $closes->format('M d, Y') }}</div>
                                            @if($isOpen && $daysLeft >= 0)
                                                <div class="job-left">{{ $daysLeft == 0 ? 'Closes today' : ($daysLeft == 1 ? '1 day left' : $daysLeft . ' days left') }}</div>
                                            @elseif($isOpen)
                                                <div class="job-left is-late">Past closing date</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="lv-pill {{ $isOpen ? 'is-added' : 'job-closed' }}">{{ $job->status }}</span>
                                        </td>
                                        <td class="text-center" width="96">
                                            <span class="lv-row-actions">
                                                <a href="{{ route('jEdit', $job->id) }}" class="lv-icon-btn" title="Edit" aria-label="Edit {{ $job->title }}">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <button type="button" value="{{ $job->id }}" class="lv-icon-btn is-danger job-delete" title="Delete" aria-label="Delete {{ $job->title }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // The delete button had no handler; this posts to the existing jDelete route.
    document.addEventListener('DOMContentLoaded', function () {
        $(document).on('click', '.job-delete', function () {
            var id = $(this).val();
            var title = $(this).closest('tr').find('.um-name').text();
            Swal.fire({
                title: 'Delete this posting?',
                text: title + ' will be removed. This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Delete'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                $.ajax({
                    type: 'POST',
                    url: "{{ route('jDelete') }}",
                    data: { id: id },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (response) {
                        if (response.status === 200) {
                            $('#example1').DataTable().row($('#tr-' + id)).remove().draw(false);
                            Swal.fire({ title: 'Deleted', icon: 'success', showConfirmButton: false, timer: 1000 });
                        } else {
                            Swal.fire({ title: 'Not deleted', text: response.message || 'Job not found.', icon: 'error' });
                        }
                    },
                    error: function () {
                        Swal.fire({ title: 'Not deleted', text: 'Something went wrong. Please try again.', icon: 'error' });
                    }
                });
            });
        });
    });
</script>
@endsection
