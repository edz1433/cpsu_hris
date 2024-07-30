@extends('layouts.master')

@section('body')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="card-header">
                        <div class="card-tools">
                            <a href="{{ route('empAdd') }}" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-user-plus"></i> ADD NEW
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-collapsed table-hover" id="example1">
                                <thead>
                                    <tr>
                                        <th>NO.</th>
                                        <th>Full Name</th>
                                        <th>Emp_ID</th> 
                                        <th>Position</th>
                                        <th>Campus</th>
                                        <th>Status</th>
                                        <th>Service</th>
                                        <th>Date Hire</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $cnt = 1; @endphp
                                    @foreach ($employee as $emp)
                                    @php
                                        $hireDate = $emp->date_hired;
                                        $currentDate = date('Y-m-d'); 

                                        $startDate = new DateTime($hireDate);
                                        $endDate = new DateTime($currentDate);

                                        $interval = $startDate->diff($endDate);

                                        $years = $interval->y;
                                        $months = $interval->m;
                                    @endphp
                                        <tr id="tr-{{ $emp->id }}">
                                            <td>{{ $cnt++ }}</td>
                                            <td>{{ strtoupper($emp->lname) }}, {{ strtoupper($emp->fname) }} {{ strtoupper($emp->mname) }}</td>
                                            <td>{{ $emp->emp_ID}}</td>
                                            <td>{{ $emp->position}}</td>
                                            <td>{{ $emp->campus_abbr}}</td>
                                            <td>
                                            @if($emp->partime_rate > 0)
                                                Part-time/JO
                                            @elseif($emp->emp_status == 2)
                                                {{ $emp->status_name }} ({{ $emp->qual }})
                                            @else
                                                {{ $emp->status_name }}
                                            @endif
                                            </td>
                                            <td>{{ $years.' years' .' '. $months. ' months' }}</td>
                                            <td>{{ isset($hireDate) ? date('F d, Y', strtotime($hireDate)) : '' }}</td>
                                            <td class="text-center">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" onchange="toggleStat(this.checked, {{ $emp->id }})" id="switch{{ $emp->id }}" {{ $emp->stat_1 == 1 ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="switch{{ $emp->id }}"></label>
                                                </div>
                                            </td>
                                            <td>
                                                <div class='d-flex align-items-center'>
                                                    <a href="{{ route('PDS', $emp->id) }}" title="PDS" class='btn btn-info btn-xs employee_edit mr-1' style='width: 30px;' value="{{ $emp->id }}">
                                                        <i class='fas fa-file-alt'></i>
                                                    </a>
                                                    {{-- <button type='button' class='btn btn-danger btn-xs employee_delete' style='width: 30px;' value="{{ $emp->id }}">
                                                        <i class='fas fa-trash'></i>
                                                    </button> --}}
                                                </div>
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
</div>
 <!-- /End Modal -->
@endsection