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
                        <li class="breadcrumb-item text-muted">Deans</li>
                    </ol> 
                </nav>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3">
                            <div class="card card-muted card-outline">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-user-plus"></i> {{ $current_route == "deanlist" ? "Add" : "Edit" }}
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <form class="form-horizontal" action="{{ $current_route == "deanlist" ? route('deanCreate') : route('deanUpdate') }}" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-md-12">
                                                    <div class="input-group">
                                                        @if(isset($deanEdit))
                                                        <input type="hidden" name="id" id="id" class="form-control" value="{{ $deanEdit->id }}">
                                                        @endif
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-user"></i>
                                                            </span>
                                                        </div>
                                                        <select class="form-control select2" id="empid" name="empid" required>
                                                            <option value=""> --- Select Employee --- </option>
                                                            @foreach($employees as $emp)
                                                                <option value="{{ $emp->id }}" @if($current_route == 'deanEdit') @if($emp->id == $deanEdit->empid) selected @endif @endif>{{ $emp->lname }} {{ $emp->fname }}</option>
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
                                                        <input type="text" class="form-control" id="designation" name="designation" placeholder="Enter Designation" value="{{ isset($deanEdit) ? $deanEdit->designation : '' }}" required>
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
                                                    <th>Designation</th>
                                                    <th>Position</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody">
                                                @foreach($deans as $index => $dean)
                                                    <tr id="tr-{{ $dean->dean_id }}">
                                                        <td width="40" class="text-center">{{ $index + 1 }}</td>
                                                        <td>{{ $dean->lname }} {{ $dean->fname }}</td>
                                                        <td>{{ $dean->designation }}</td>
                                                        <td>{{ $dean->position == 1 ? 'Dean' : 'Other' }}</td>
                                                        <td class="text-center">
                                                            <a href="{{ route('deanEdit', $dean->dean_id) }}" class="btn btn-info btn-xs">
                                                                <i class="fas fa-exclamation-circle"></i>
                                                            </a>
                                                            <button value="{{ $dean->dean_id }}" class="btn btn-danger btn-xs dean-delete">
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
