@extends('layouts.master')
@section('body')
@php
    $current_route=request()->route()->getName();
@endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-2">
            @include('drive.submenu')
        </div>
        <div class="col-lg-10">
            <div class="card card-info card-outline">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        @if($guard == "web")
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-success1"><i class="fas fa-dashboard"></i> Dashboard</a></li>
                        @endif
                        <li class="breadcrumb-item"><a href="{{ route('drive') }}" class="text-success1">Drive</a></li>
                        <li class="breadcrumb-item text-muted">Pmt</li>
                    </ol> 
                </nav>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3">
                            <div class="card card-muted card-outline">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-user-plus"></i> {{ $current_route == "pmtlist" ? "Add" : "Edit" }}
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <form class="form-horizontal" action="{{ $current_route == "pmtlist" ? route('pmtCreate') : route('pmtUpdate') }}" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-md-12">
                                                    <div class="input-group">
                                                        @if(isset($pmtEdit))
                                                        <input type="hidden" name="pmt_id" id="pmt_id" class="form-control" value="{{ $pmtEdit->id }}">
                                                        @endif
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-user"></i>
                                                            </span>
                                                        </div>
                                                        <select class="form-control select2" id="empid" name="empid" required>
                                                            <option value=""> --- Select Employee --- </option>
                                                            @foreach($employees as $emp)
                                                                <option value="{{ $emp->id }}" @if($current_route == 'pmtEdit') @if($emp->id == $pmtEdit->empid) selected @endif @endif>{{ $emp->lname }} {{ $emp->fname }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                
                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-md-12">
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-user"></i>
                                                            </span>
                                                        </div>
                                                        <select class="form-control select2" id="position" name="position" required>
                                                            <option value=""> --- Select Position --- </option>
                                                            <option value="1" @if($current_route == 'pmtEdit' && $pmtEdit->position == 1) selected @endif>Performance Management Team</option>
                                                            <option value="2" @if($current_route == 'pmtEdit' && $pmtEdit->position == 2) selected @endif>Local PMT</option>
                                                            <option value="3" @if($current_route == 'pmtEdit' && $pmtEdit->position == 3) selected @endif>PMT Secretariat</option>
                                                            <option value="4" @if($current_route == 'pmtEdit' && $pmtEdit->position == 4) selected @endif>Dean</option>
                                                        </select>
                                                    </div>    
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-md-12">
                                                    <button type="submit" name="btn-submit" class="btn btn-success btn-sm">
                                                        <i class="fas fa-save"></i> Save
                                                    </button>
                                                </div>
                                            </div>
                                        </div>    
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-9">
                            <div class="card card-muted card-outline">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="example1" class="table table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Full Name</th>
                                                    <th>Position</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody">
                                                @foreach($pmts as $index => $pmt)
                                                    <tr id="tr-{{ $pmt->pmtid }}">
                                                        <td width="40" class="text-center">{{ $index + 1 }}</td>
                                                        <td>{{ $pmt->lname }} {{ $pmt->fname }}</td>
                                                        <td>
                                                            @if($pmt->position == 1)
                                                                Performance Management Team
                                                            @elseif($pmt->position == 2)
                                                                Local PMT
                                                            @elseif($pmt->position == 3)
                                                                PMT Secretariat
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            <a href="{{ route('pmtEdit', $pmt->pmtid) }}" class="btn btn-info btn-xs">
                                                                <i class="fas fa-exclamation-circle"></i>
                                                            </a>
                                                            <button value="{{ $pmt->pmtid }}" class="btn btn-danger btn-xs pmt-delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
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
        </div>
    </div>
</div>
@endsection
