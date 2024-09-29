@extends('layouts.master')

@section('body')
@include('emp.style')
<style>
    th,td{
        padding: 3px !important;
    }
    .modal-body img {
        max-width: 100%; 
        height: auto; 
        max-height: 80vh; 
    }
    .scrollable {
        height: 600px;
        overflow-y: auto;
        border: 1px solid #ddd;
        padding: 10px;
    }
</style>
<section class="content">
    <div class="container-fluid">
        <div class="row">
            @include('emp.submenu-side')
            <div class="col-lg-9">
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h2 class="card-title text-success1">
                            <b>VOLUNTARY WORKS</b>
                        </h2>
                    </div>
                    <div class="card-body">
                        <div id="accordion">
                            <div class="card card-muted">
                            <div class="card-header">
                                <h4 class="card-title w-100">
                                <a class="d-block w-100 collapsed text-success1" data-toggle="collapse" href="#collapseOne" aria-expanded="false">
                                    <b>FORM</b> 
                                </a>
                                </h4>
                            </div>
                            <div id="collapseOne" class="collapse  {{ (count($voluntaryworks) > 0) ? '' : 'show' }} {{ isset($voluntaryworksedit) ? 'show' : '' }}" data-parent="#accordion" style="">
                                <div class="card-body bg-form">
                                    <form class="form-horizontal" action="{{ isset($voluntaryworksedit) ? route('voluntaryworksUpdate', $voluntaryworksedit->id) : route('voluntaryworksCreate') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @if(isset($voluntaryworksedit))
                                            <input type="hidden" name="id" value="{{ $voluntaryworksedit->id }}">
                                        @endif
                                        <input type="hidden" name="empid" value="{{ $employee->emp_ID }}">
                                        
                                        <div class="form-row lbel mtop">
                                            <div class="col-md-6">
                                                <label class="badge badge-secondary text-wrap text-center lbel">Name & Address of Organization (Write in Full)</label>
                                                <input type="text" name="org_name" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($voluntaryworksedit) ? $voluntaryworksedit->org_name : '' }}" autocomplete="off" required>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="badge badge-secondary text-wrap text-center lbel">Inclusive Dates</label>
                                                <div style="display: flex; justify-content: space-between;">
                                                    <input type="date" id="inc_date1" name="inc_date1" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($voluntaryworksedit) ? $voluntaryworksedit->inc_date1 : '' }}" autocomplete="off" style="flex: 1; margin-right: 5px;" required>
                                                    <input type="date" id="inc_date2" name="inc_date2" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($voluntaryworksedit) ? $voluntaryworksedit->inc_date2 : '' }}" autocomplete="off" style="flex: 1; margin-left: 5px;" required>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="badge badge-secondary text-wrap lbel">Number of Hours</label>
                                                <input type="number" name="num_hours" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($voluntaryworksedit) ? $voluntaryworksedit->num_hours : '' }}" autocomplete="off" required>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label class="badge badge-secondary text-wrap lbel">Position / Nature of Work</label>
                                                <input type="text" name="position" class="form-control form-control-sm" placeholder="N/A" value="{{ isset($voluntaryworksedit) ? $voluntaryworksedit->position : '' }}" autocomplete="off" required>
                                            </div>
                                    
                                            <div class="col-md-12 mt-2">
                                                <button type="submit" name="btn-submit" class="btn btn-success btn-sm mt-1 float-right">
                                                    <i class="fas fa-save"></i> {{ isset($voluntaryworksedit) ? 'Update' : 'Submit' }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>             
                                </div>
                            </div>
                            </div>
                        </div>
                        <div class="scrollable">                    
                            <table class="table table-bordered table-hover mt-2">
                                <div class="card-header">
                                    <h3 class="card-title"></h3>
                                    <div class="card-tools">
                                        <div class="input-group input-group-sm" style="width: 150px;">
                                            <input type="text" name="table_search" class="form-control float-right" placeholder="Search">
                                            <div class="input-group-append">
                                                <button type="submit" class="btn btn-default">
                                                <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </div>    
                                    </div>
                                </div>
                                @foreach($voluntaryworks as $vwork)
                                <tbody>
                                    <tr class="voluntaryworks-row row-{{ $vwork->id }}">
                                        <th class="align-middle">NAME & ADDRESS OF ORGANIZATION</th> 
                                        <td class="align-middle">{{ $vwork->org_name }}</td>
                                        <th class="text-center align-middle" rowspan="9" width="5%">
                                            <a href="{{ route('voluntaryworksEdit', ['id' => $empid, 'eid' => $vwork->id]) }}" class="btn btn-info btn-sm mb-2" title="Edit">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <button class="btn btn-danger btn-sm mb-2 voluntaryworks_delete" value="{{ $vwork->id }}" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </th> 
                                    </tr>
                                    <tr class="voluntaryworks-row row-{{ $vwork->id }}">
                                        <th class="align-middle" width="50%">INCLUSIVE DATES</th>
                                        <td class="align-middle">
                                            {{ \Carbon\Carbon::parse($vwork->inc_date1)->format('m/d/Y') }} - 
                                            {{ \Carbon\Carbon::parse($vwork->inc_date2)->format('m/d/Y') }}
                                        </td>                                                                                                                
                                    </tr>
                                    <tr class="voluntaryworks-row row-{{ $vwork->id }}">
                                        <th class="align-middle">NUMBER OF HOURS</th>
                                        <td class="align-middle">{{ number_format($vwork->num_hours).' HOURS' }}</td>
                                    </tr>
                                    <tr class="voluntaryworks-row row-{{ $vwork->id }}">
                                        <th class="align-middle">POSITION / NATURE OF WORK</th>
                                        <td class="align-middle">{{ $vwork->position }}</td>
                                    </tr>
                                </tbody>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection