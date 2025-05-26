@extends('layouts.master')

@section('body')
<style>  
    #table-form {
        width: 100%;
        font-size: 10px;
    }

    #table-form td, th{
        border: 1px solid rgb(92, 85, 85);
        padding: 1px;
    }
    .b-none{
        border: none !important;
        width: 18px !important;
    }

    .btn-outline-secondary {
        border-radius: 50px;
        width: 30px !important;
        height: 30px !important;
    }
    .border-b-n{
        border-bottom: none;
    }
    .modal-dialog {
        max-width: 90%;
        height: 90%;
        margin: 30px auto;
    }
</style>
@php
    $selectedEmployees = \App\Models\SpmsAsignatory::where('pr_number', 0001)
        ->join('employees', 'spms_asignatories.empid', '=', 'employees.emp_ID')
        ->select('employees.fname', 'employees.lname', 'employees.mname', 'spms_asignatories.*')
        ->get();
@endphp
@include('drive.modal-mfo')
<div class="modal fade" id="modal-rating" tabindex="-1" role="dialog" aria-labelledby="modal-prform" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <iframe src="{{ asset('Uploads/spms-rating.pdf') }}" frameborder="0" style="width: 100%; height: 80vh;"></iframe>
            </div>
        </div>
    </div>
</div>
<div class="d-flex justify-content-end align-items-center gap-2 mb-3">
    <div class="input-group" style="width: auto;">
        <select class="form-control form-control-sm" id="categorySelect">
            <option value="0" {{ ($cat == 0) ? 'selected' : '' }} >All</option>
            <option value="1" {{ ($cat == 1) ? 'selected' : '' }}>1st Quarter</option>
            <option value="2" {{ ($cat == 2) ? 'selected' : '' }}>2nd Quarter</option>
        </select>
        <div class="input-group-append" style="margin-right: 5px;">
            <span class="input-group-text"><i class="fas fa-filter"></i></span>
        </div>
    </div>
    <button type="submit" class="btn btn-info btn-sm" data-toggle="modal" data-target="#modal-rating">
        <i class="fas fa-star"></i> Rating
    </button>
</div>

<div style="max-height: 500px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.25rem; padding: 10px;">
<table id="table-form">
    <thead>
        <tr>
            <th rowspan="3" class="text-center">MFO/PAPs</th>
            <th class="text-center" width="180">Success Indicators</th>
            <th rowspan="3" class="text-center">Link to Source</th>
            <th colspan="2" class="text-center" >Evidence</th>
            <th rowspan="3" class="text-center" >Allotted<br>Budget</th>
            <th rowspan="3" class="text-center">Division/<br>Individuals<br>Accountable</th>
            <th rowspan="2"colspan="7" class="text-center border-b-n" ></th>
            <th class="text-center">Remarks/ Accomplishment</th>
            <th rowspan="2"></th>
        </tr>
        <tr>
            <th rowspan="2" class="text-center">(Targets + Measures)</th>
            <th rowspan="2" class="text-center" width="100">Individual<br>Support<br>Documents</th>
            <th rowspan="2" class="text-center" width="100">Report of<br>Supervisor/<br>Other Offices</th>
            <th rowspan="2"></th>
        </tr>
        <tr>
            <th rowspan="2" class="text-center" width="130">Q</th>
            <th rowspan="2" class="text-center" width="30"></th>
            <th rowspan="2" class="text-center" width="110">E</th>
            <th rowspan="2" class="text-center" width="30"></th>
            <th rowspan="2" class="text-center" width="130">T</th>
            <th rowspan="2" class="text-center" width="30"></th>
            <th rowspan="2" class="text-center" width="30">A</th>
            <th rowspan="2"></th>
        </tr>
    </thead>
    <tbody id="tbody-form" style="max-height: 300px; overflow-y: auto;">
        <tr>
            <td><b>{{ $prs[0]->mfo ?? '' }} ({{ $prs[0]->percent ?? '' }}%)</b></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td class="text-center"><span class="badge badge-danger rounded-circle">X</span></td>
            @if(isset($prs[0]))
                <td class="b-none text-center">
                    <i class="fas fa-plus fa-lg text-success1 pl-1" style="cursor: pointer;"
                    data-toggle="modal"
                    data-cat="1"
                    data-id="{{ $prs[0]->id }}"
                    data-mfo="{{ $prs[0]->mfo ?? '' }}"
                    data-percent="{{ $prs[0]->percent ?? '' }}"
                    data-target="#createOpcrMfoModal">
                    </i>
                </td>
            @else
                <td class="b-none text-center"></td>
            @endif
        </tr>

        
        @foreach($cores as $core)
            <tr>
                <td>{{ $core->mfo ?? '' }} ({{ $core->percent ?? '' }}%)</td>
                <td class="text-center">{{ $core->target ?? '' }}</td>
                <td class="text-center">{{ $core->in_support ?? '' }}</td>
                <td class="text-center"></td>
                <td class="text-center"></td>
                <td class="text-center"></td>
                <td class="text-center"></td>
                <td class="text-center">{{ $core->report_sup ?? '' }}</td>
                <td class="text-center">{{ $core->alloted ?? '' }}</td>
                <td class="text-center">{{ $core->div_account ?? '' }}</td>
                <td class="text-center">{{ $core->qrate ?? '' }}</td>
                <td class="text-center">{{ $core->erate ?? '' }}</td>
                <td class="text-center">{{ $core->trate ?? '' }}</td>
                <td class="text-center">{{ $core->a ?? '' }}</td>
                <td class="text-center">{{ $core->remarks ?? '' }}</td>
                <td class="text-center"><span class="badge badge-danger rounded-circle">X</span></td>
                <td class="b-none text-center">
                    <i class="fas fa-plus text-secondary pl-1 mfo-data" data-toggle="modal" style="cursor: pointer;" data-target="#opcrMfoData" data-mfoid="{{ $core->id }}"></i>
                </td>
            </tr>
            @php
                $filteredOpcrMfoDatas = in_array($cat, [1, 2])
                    ? $opcrmfodatas->where('opcr_mfo_id', $core->id)->where('category', $cat)
                    : $opcrmfodatas->where('opcr_mfo_id', $core->id);
            @endphp
        
            @foreach($filteredOpcrMfoDatas as $opcrmfodata)
            <tr id="mfodata{{ $opcrmfodata->id }}-{{ $opcrmfodata->opcr_mfo_id }}" onclick="showOpcrMfoData({{ $opcrmfodata->id }},{{ $opcrmfodata->opcr_mfo_id }})" style="cursor: pointer;">
                <td class="text-left align-top">{!! $opcrmfodata->mfo !!}</td>
                <td class="text-left pl-1">{!! $opcrmfodata->target !!}</td>
                <td class="text-center"></td>
                <td class="text-center">{!! $opcrmfodata->in_support !!}</td>
                <td class="text-center"></td>
                <td class="text-center"></td>
                <td class="text-center">{!! $opcrmfodata->div_account !!}</td>
                <td class="text-center">{!! $opcrmfodata->quality !!}</td>
                <td class="text-center">{!! $opcrmfodata->q_score !!}</td>
                <td class="text-center">{!! nl2br(e($opcrmfodata->efficiency)) !!}</td>
                <td class="text-center">{!! $opcrmfodata->e_score !!}</td>
                <td class="text-center">{!! $opcrmfodata->timeliness !!}</td>
                <td class="text-center">{!! $opcrmfodata->t_score !!}</td>
                <td class="text-center">{!! $opcrmfodata->average !!}</td>
                <td class="text-center">{!! $opcrmfodata->remarks !!}</td>
                <td class="text-center"><span class="badge badge-danger rounded-circle">X</span></td>
            </tr>
            @endforeach
        @endforeach

        
        <tr>
            <td><b>{{ $prs[1]->mfo ?? '' }} ({{ $prs[1]->percent ?? '' }}%)</b></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td class="text-center"><span class="badge badge-danger rounded-circle">X</span></td>
            @if(isset($prs[1]))
                <td class="b-none text-center">
                    <i class="fas fa-plus fa-lg text-success1 pl-1" style="cursor: pointer;"
                    data-toggle="modal"
                    data-cat="2"
                    data-id="{{ $prs[1]->id }}"
                    data-mfo="{{ $prs[1]->mfo ?? '' }}"
                    data-percent="{{ $prs[1]->percent ?? '' }}"
                    data-target="#createOpcrMfoModal">
                    </i>
                </td>
            @else
                <td class="b-none text-center"></td>
            @endif
        </tr>
        @foreach($strats as $strat)
        <tr>
            <td>{{ $strat->mfo ?? '' }} ({{ $strat->percent ?? '' }}%)</td>
            <td class="text-center">{{ $strat->target ?? '' }}</td>
            <td class="text-center">{{ $strat->in_support ?? '' }}</td>
            <td class="text-center"></td>
            <td class="text-center"></td>
            <td class="text-center"></td>
            <td class="text-center"></td>
            <td class="text-center">{{ $strat->report_sup ?? '' }}</td>
            <td class="text-center">{{ $strat->alloted ?? '' }}</td>
            <td class="text-center">{{ $strat->div_account ?? '' }}</td>
            <td class="text-center">{{ $strat->qrate ?? '' }}</td>
            <td class="text-center">{{ $strat->erate ?? '' }}</td>
            <td class="text-center">{{ $strat->trate ?? '' }}</td>
            <td class="text-center">{{ $strat->a ?? '' }}</td>
            <td class="text-center">{{ $strat->remarks ?? '' }}</td>
            <td class="text-center"><span class="badge badge-danger rounded-circle">X</span></td>
            <td class="b-none text-center">
                <i class="fas fa-plus text-secondary pl-1 mfo-data" data-toggle="modal" style="cursor: pointer;" data-target="#opcrMfoData" data-mfoid="{{ $strat->id }}"></i>
            </td>
             @php
                $filteredopcrmfodatas = in_array($cat, [1, 2])
                    ? $opcrmfodatas->where('opcr_mfo_id', $strat->id)->where('category', $cat)
                    : $opcrmfodatas->where('opcr_mfo_id', $strat->id);
            @endphp
        
            {{-- @php dd($opcrmfodatas); @endphp --}}
            @foreach($filteredopcrmfodatas as $opcrmfodata)
                <tr id="mfodata{{ $opcrmfodata->id }}-{{ $opcrmfodata->opcr_mfo_id }}" onclick="showOpcrMfoData({{ $opcrmfodata->id }}, {{ $opcrmfodata->opcr_mfo_id }})" style="cursor: pointer;">
                    <td class="text-left align-top">{!! $opcrmfodata->mfo !!}</td>
                    <td class="text-left pl-1">{!! $opcrmfodata->target !!}</td>
                    <td class="text-center"></td>
                    <td class="text-center">{!! $opcrmfodata->in_support !!}</td>
                    <td class="text-center"></td>
                    <td class="text-center"></td>
                    <td class="text-center">{!! $opcrmfodata->div_account !!}</td>
                    <td class="text-center">{!! $opcrmfodata->quality !!}</td>
                    <td class="text-center">{!! $opcrmfodata->q_score !!}</td>
                    <td class="text-center">{!! $opcrmfodata->efficiency !!}</td>
                    <td class="text-center">{!! $opcrmfodata->e_score !!}</td>
                    <td class="text-center">{!! $opcrmfodata->timeliness !!}</td>
                    <td class="text-center">{!! $opcrmfodata->t_score !!}</td>
                    <td class="text-center">{!! $opcrmfodata->average !!}</td>
                    <td class="text-center">{!! $opcrmfodata->remarks !!}</td>
                    <td class="text-center"><span class="badge badge-danger rounded-circle">X</span></td>
                </tr>
            @endforeach
        </tr>
        @endforeach
        <tr>
            <td><b>{{ $prs[2]->mfo ?? '' }} ({{ $prs[2]->percent ?? '' }}%)</b></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td class="text-center"><span class="badge badge-danger rounded-circle">X</span></td>
            @if(isset($prs[2]))
                <td class="b-none text-center">
                    <i class="fas fa-plus fa-lg text-success1 pl-1" style="cursor: pointer;"
                    data-toggle="modal"
                    data-cat="3"
                    data-id="{{ $prs[2]->id }}"
                    data-mfo="{{ $prs[2]->mfo ?? '' }}"
                    data-percent="{{ $prs[2]->percent ?? '' }}"
                    data-target="#createOpcrMfoModal">
                    </i>
                </td>
            @endif
        </tr>
        @foreach($supports as $supp)
        <tr>
            <td>{{ $supp->mfo ?? '' }} ({{ $supp->percent ?? '' }}%)</td>
            <td class="text-center">{{ $supp->target ?? '' }}</td>
            <td class="text-center">{{ $supp->in_support ?? '' }}</td>
            <td class="text-center"></td>
            <td class="text-center"></td>
            <td class="text-center"></td>
            <td class="text-center"></td>
            <td class="text-center">{{ $supp->report_sup ?? '' }}</td>
            <td class="text-center">{{ $supp->alloted ?? '' }}</td>
            <td class="text-center">{{ $supp->div_account ?? '' }}</td>
            <td class="text-center">{{ $supp->qrate ?? '' }}</td>
            <td class="text-center">{{ $supp->erate ?? '' }}</td>
            <td class="text-center">{{ $supp->trate ?? '' }}</td>
            <td class="text-center">{{ $supp->a ?? '' }}</td>
            <td class="text-center">{{ $supp->remarks ?? '' }}</td>
            <td class="text-center"><span class="badge badge-danger rounded-circle">X</span></td>
            <td class="b-none text-center">
                <i class="fas fa-plus text-secondary pl-1 mfo-data" data-toggle="modal" style="cursor: pointer;" data-target="#opcrMfoData" data-mfoid="{{ $supp->id }}"></i>
            </td>
            @php
                $filteredopcrmfodatas = in_array($cat, [1, 2])
                    ? $opcrmfodatas->where('opcr_mfo_id', $supp->id)->where('category', $cat)
                    : $opcrmfodatas->where('opcr_mfo_id', $supp->id);
            @endphp
        
            @foreach($filteredopcrmfodatas as $opcrmfodata)
                <tr id="mfodata{{ $opcrmfodata->id }}-{{ $opcrmfodata->opcr_mfo_id }}" onclick="showOpcrMfoData({{ $opcrmfodata->id }},{{ $opcrmfodata->opcr_mfo_id }})" style="cursor: pointer;">
                    <td class="text-left align-top">{!! $opcrmfodata->mfo !!}</td>
                    <td class="text-left pl-1">{!! $opcrmfodata->target !!}</td>
                    <td class="text-center"></td>
                    <td class="text-center">{!! $opcrmfodata->in_support !!}</td>
                    <td class="text-center"></td>
                    <td class="text-center"></td>
                    <td class="text-center">{!! $opcrmfodata->div_account !!}</td>
                    <td class="text-center">{!! $opcrmfodata->quality !!}</td>
                    <td class="text-center">{!! $opcrmfodata->q_score !!}</td>
                    <td class="text-center">{!! $opcrmfodata->efficiency !!}</td>
                    <td class="text-center">{!! $opcrmfodata->e_score !!}</td>
                    <td class="text-center">{!! $opcrmfodata->timeliness !!}</td>
                    <td class="text-center">{!! $opcrmfodata->t_score !!}</td>
                    <td class="text-center">{!! $opcrmfodata->average !!}</td>
                    <td class="text-center">{!! $opcrmfodata->remarks !!}</td>
                    <td class="text-center"><span class="badge badge-danger rounded-circle">X</span></td>
                </tr>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>
</div>

<div class="row mt-2 mb-3">
    <div class="col-md-12 text-right mt-3" style="cursor: pointer;">
        <i class="fas fa-cog mr-2" style="font-size: 16px;" data-toggle="modal" data-target="#setupModal"></i>
    </div>
    <div class="col-md-12 text-center">
        <div class="row">
            @foreach ($selectedEmployees as $asignatory)
                @php
                    $fullName = $asignatory->fname . ' ' .
                                ($asignatory->mname ? strtoupper(substr($asignatory->mname, 0, 1)) . '. ' : '') .
                                $asignatory->lname ;
                @endphp
                <div class="col text-center">
                    <div><strong>_________________________________</strong></div>
                    <div><strong>{{ $fullName ?? 'N/A' }}{{ ($asignatory->suffixes) ? ', '.$asignatory->suffixes : '' }}</strong></div>
                    <div>{{ $asignatory->designation ?? 'N/A' }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    function saveSetup() {
        // Logic to save the setup
        const signatories = [];
        for (let i = 1; i <= 5; i++) {
            const value = document.getElementById(`signatory${i}`).value;
            if (value) {
                signatories.push(value);
            }
        }
        console.log('Saved Signatories:', signatories);
        $('#setupModal').modal('hide');
    }
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const empid = "{{ $empid ?? '' }}";
    const prnumber = "{{ $prnumber ?? '' }}";

    document.getElementById('categorySelect').addEventListener('change', function () {
        const cat = this.value;

        // Use the route base and replace the params dynamically
        const url = `{{ route('per-rating', ['cat' => 'CAT_PLACEHOLDER', 'empid' => 'EMPID_PLACEHOLDER', 'prnumber' => 'PR_PLACEHOLDER']) }}`
            .replace('CAT_PLACEHOLDER', cat)
            .replace('EMPID_PLACEHOLDER', empid)
            .replace('PR_PLACEHOLDER', prnumber);

        window.location.href = url;
    });
</script>
<script>
    function showOpcrMfoData(id, mfoid) {
        Swal.fire({
            title: 'Choose an action',
            icon: 'question',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: 'Asign',
            denyButtonText: 'Edit',
            cancelButtonText: 'Delete',
            reverseButtons: false,
            customClass: {
                confirmButton: 'btn btn-primary mx-1',
                denyButton: 'btn btn-info mx-1',
                cancelButton: 'btn btn-danger mx-1'
            },
            buttonsStyling: false // Needed to apply Bootstrap styles
        }).then((result) => {
            if (result.isConfirmed) {
                $('#opcr-mfo-data-id').val(id);
                $('#asign-to-dpcr').modal('show');
            } else if (result.isDenied) {
                editOpcrData(id);
                $('#opcr-mfo-id').val(mfoid);

                $.ajax({
                    url: `{{ route('opcrmfoEditData', ':id') }}`.replace(':id', id),
                    method: 'GET',
                    success: function (data) {
                        $('#category').val(data.category);
                        $('#opcr_by').val(data.opcr_by);
                        $('#mfo').val(data.mfo);
                        $('#target').val(data.target);
                        $('#in_support').val(data.in_support);
                        $('#report_sup').val(data.report_sup);
                        $('#div_account').val(data.div_account);
                        $('#quality').val(data.quality);
                        $('#efficiency').val(data.efficiency);
                        $('#timeliness').val(data.timeliness);
                    },
                    error: function () {
                        Swal.fire('Error', 'Unable to fetch data for editing.', 'error');
                    }
                });
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                confirmDeleteOpcrData(id, mfoid);
            }
        });
    }

    function editOpcrData(id) {
        // Set hidden input value
        document.getElementById('opcrdata_id').value = id;

        // Show the modal
        $('#opcrMfoData').modal('show');
    }

    function confirmDeleteOpcrData(id,mfoid) {
        Swal.fire({
            title: 'Delete this entry?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Back'
        }).then((result) => {
            if (!result.isConfirmed) return;

            const url = `{{ route('opcrmfoDeleteData', ':id') }}`
                        .replace(':id', id);

            fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin' // Ensures cookies are sent with the request
            })
            .then(response => {
            if (!response.ok) {
                // still try to parse JSON error
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
            })
            .then(data => {
                Swal.fire('Deleted!', data.success, 'success')
                    .then(() => location.reload());
                $(`#mfodata${id}-${mfoid}`).fadeOut(1500, function () {
                    // Optional callback after fadeOut
                });
            })
            .catch(err => {
                const msg = err.message || err.error || 'Something went wrong.';
                Swal.fire('Error!', msg, 'error');
            });
        });
    }

    $(document).on('click', '.mfo-data', function (event) {
        var mfoid = $(this).data('mfoid');
        $('#opcr-mfo-id').val(mfoid);
        $('#opcrdata_id').val(0);

        $('#opcr_by').val('');
        $('#mfo').val('');
        $('#target').val('');
        $('#in_support').val('');
        $('#report_sup').val('');
        $('#div_account').val('');
        $('#quality').val('');
        $('#efficiency').val('');
        $('#timeliness').val('');
    });

</script>
@endsection