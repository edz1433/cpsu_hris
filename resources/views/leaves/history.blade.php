@extends('layouts.master')

@section('body')
@include('leaves.style')
<style>
    .modal-content {
        background: rgba(255, 255, 255, 0.515);
        border: none;
        box-shadow: none;
    }
    
    .modal-backdrop {
        background-color: transparent;
    }
</style>
<section class="content">
<div class="container-fluid">
    <div class="row">
        @include("leaves.side-menu")
        <div class="col-lg-9">
            <div class="card card-info card-outline">
                <div class="card-header">
                    @include("leaves.top-menu")
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <table class="table table-collapsed table-hover" id="example1">
                            <thead>
                                <tr>
                                    <th>LEAVE TYPE</th>
                                    <th>INCLUSIVE DATES</th>
                                    <th>DAYS APPLIED</th>
                                    <th>DAYS W/OUT PAY</th>
                                    <th>DATE OF FILING</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php 
                                    $leavetype = [
                                        '1' => 'Vacation Leave',
                                        '2' => 'Mandatory/Forced Leave',
                                        '3' => 'Sick Leave',
                                        '4' => 'Maternity Leave',
                                        '5' => 'Paternity Leave',
                                        '6' => 'Special Privilege Leave',
                                        '7' => 'Solo Parent Leave',
                                        '8' => 'Study Leave',
                                        '9' => '10-Day VAWC Leave',
                                        '10' => 'Rehabilitation Privilege',
                                        '11' => 'Special Leave Benefits for Women',
                                        '12' => 'Special Emergency (Calamity) Leave',
                                        '13' => 'Adoption Leave',
                                        '14' => 'Others',
                                    ];
                                @endphp
                                @foreach($leaveApplication as $leaves)
                                    @php
                                        if (strpos($leaves->date_range, 'to') !== false) {
                                            [$startDate, $endDate] = explode(' to ', $leaves->date_range);
                                            
                                            $formattedStartDate = \Carbon\Carbon::parse($startDate)->format('M d, Y');
                                            $formattedEndDate = \Carbon\Carbon::parse($endDate)->format('M d, Y');
                                        } else {
                                            $formattedStartDate = \Carbon\Carbon::parse($leaves->date_range)->format('M d, Y');
                                            $formattedEndDate = null;
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ strtoupper($leavetype[$leaves->leave_type]) }}</td>
                                        <td>{{ ($formattedEndDate) ? strtoupper($formattedStartDate) : '' }} {{ ($formattedEndDate) ? strtoupper($formattedEndDate) : '' }}</td>
                                        <td>{{ $leaves->days }}</td>
                                        <td>{{ ($leaves->day_wpay) ? $leaves->day_wpay : '' }}</td>
                                        <td>{{ isset($leaves->date_filing) ? strtoupper(\Carbon\Carbon::parse($leaves->date_filing)->format('M d, Y')) : '' }}</td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm" data-id="{{ $leaves->id }}" data-toggle="modal" data-target="#pdfModal"><i class="fas fa-file-pdf"></i> View</button>
                                        </td>
                                    </tr>
                                @endforeach
                                @foreach($leaveApplication1 as $leaves)
                                    @php
                                        if (strpos($leaves->date_range, 'to') !== false) {
                                            [$startDate, $endDate] = explode(' to ', $leaves->date_range);
                                            
                                            $formattedStartDate = \Carbon\Carbon::parse($startDate)->format('M d, Y');
                                            $formattedEndDate = \Carbon\Carbon::parse($endDate)->format('M d, Y');
                                        } else {
                                            $formattedStartDate = \Carbon\Carbon::parse($leaves->date_range)->format('M d, Y');
                                            $formattedEndDate = null;
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ strtoupper($leavetype[$leaves->leave_type]) }}</td>
                                        <td>{{ ($formattedEndDate) ? strtoupper($formattedStartDate) : '' }} {{ ($formattedEndDate) ? strtoupper($formattedEndDate) : '' }}</td>
                                        <td>{{ $leaves->days }}</td>
                                        <td>{{ ($leaves->day_wpay) ? $leaves->day_wpay : '' }}</td>
                                        <td>{{ isset($leaves->date_filing) ? strtoupper(\Carbon\Carbon::parse($leaves->date_filing)->format('M d, Y')) : '' }}</td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm" data-id="{{ $leaves->id }}" data-toggle="modal" data-target="#pdfModal"><i class="fas fa-file-pdf"></i> View</button>
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
<div class="modal fade" id="pdfModal" tabindex="-1" role="dialog" aria-labelledby="pdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <iframe id="pdfIframe" src="" width="100%" height="600px" style="border:none;"></iframe>
            </div>
        </div>
    </div>
</div>
</section>
@endsection
