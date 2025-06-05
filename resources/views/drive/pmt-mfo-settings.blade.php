@extends('layouts.master')
@section('body')
@php
    $current_route=request()->route()->getName();
    $isUserInPmts = in_array($userid, $pmtsmember ?? []);
    if ($guard == 'employee' && !$isUserInPmts) {
        echo "<script>window.location.href = '" . route('drive') . "';</script>";
        exit;
    }
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
                        <li class="breadcrumb-item text-muted">MFO Settings</li>
                    </ol> 
                </nav>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card card-muted card-outline">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-cogs"></i> MFO Settings
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-3 card">
                                            <form class="form-horizontal" action="{{ $current_route == 'mfoSettings' ? route('mfoSettingsCreate') : route('mfoSettingsUpdate') }}" method="POST">
                                                @csrf
                                                @if(isset($mfoEdit))
                                                    <input type="hidden" name="mfo_id" id="mfo_id" class="form-control" value="{{ $mfoEdit->id }}">
                                                @endif
                                                <div class="form-group">
                                                    <div class="form-row">
                                                        <div class="col-md-12">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text">
                                                                        <i class="fas fa-tags"></i>
                                                                    </span>
                                                                </div>
                                                                <input type="text" name="category" id="category" class="form-control form-control-sm" placeholder="Enter category" value="{{ isset($mfoEdit) ? $mfoEdit->category : '' }}" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="badge badge-secondary mb-1">Core Functions</span>
                                                <div class="form-group">
                                                    <div class="form-row">
                                                        <div class="col-md-12">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text">
                                                                        <i class="fas fa-cog"></i>
                                                                    </span>
                                                                </div>
                                                                <input type="number" name="core_mfo1" id="core_mfo1" class="form-control form-control-sm" placeholder="MFO1" value="{{ isset($mfoEdit) ? $mfoEdit->core_mfo1 : '' }}" required>
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
                                                                        <i class="fas fa-cog"></i>
                                                                    </span>
                                                                </div>
                                                                <input type="number" name="core_mfo2" id="core_mfo2" class="form-control form-control-sm" placeholder="MFO2" value="{{ isset($mfoEdit) ? $mfoEdit->core_mfo2 : '' }}" required>
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
                                                                        <i class="fas fa-cog"></i>
                                                                    </span>
                                                                </div>
                                                                <input type="number" name="core_mfo3" id="core_mfo3" class="form-control form-control-sm" placeholder="MFO3" value="{{ isset($mfoEdit) ? $mfoEdit->core_mfo3 : '' }}" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="badge badge-secondary mb-1">Strategic Functions</span>
                                                <div class="form-group">
                                                    <div class="form-row">
                                                        <div class="col-md-12">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text">
                                                                        <i class="fas fa-bullseye"></i>
                                                                    </span>
                                                                </div>
                                                                <input type="number" name="strategic_mfo4" id="strategic_mfo4" class="form-control form-control-sm" placeholder="MFO4" value="{{ isset($mfoEdit) ? $mfoEdit->strategic_mfo4 : '' }}" required>
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
                                                                        <i class="fas fa-bullseye"></i>
                                                                    </span>
                                                                </div>
                                                                <input type="number" name="strategic_mfo5" id="strategic_mfo5" class="form-control form-control-sm" placeholder="MFO5" value="{{ isset($mfoEdit) ? $mfoEdit->strategic_mfo5 : '' }}" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="badge badge-secondary mb-1">Support Functions</span>
                                                <div class="form-group">
                                                    <div class="form-row">
                                                        <div class="col-md-12">
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text">
                                                                        <i class="fas fa-hands-helping"></i>
                                                                    </span>
                                                                </div>
                                                                <input type="number" name="support_mfo4" id="support_mfo4" class="form-control form-control-sm" placeholder="MFO4" value="{{ isset($mfoEdit) ? $mfoEdit->support_mfo4 : '' }}" required>
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
                                                                        <i class="fas fa-hands-helping"></i>
                                                                    </span>
                                                                </div>
                                                                <input type="number" name="support_mfo5" id="support_mfo5" class="form-control form-control-sm" placeholder="MFO5" value="{{ isset($mfoEdit) ? $mfoEdit->support_mfo5 : '' }}" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="form-row">
                                                        <div class="col-md-12">
                                                            <button type="submit" class="btn btn-success btn-sm">
                                                                <i class="fas fa-save"></i> Save
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="col-lg-9 card">
                                            <table id="example1" class="table table-bordered table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Category</th>
                                                        <th colspan="3" class="text-center">Core Functions</th>
                                                        <th></th>
                                                        <th colspan="2" class="text-center">Strategic Functions</th>
                                                        <th></th>
                                                        <th colspan="2" class="text-center">Support Functions</th>
                                                        <th></th>
                                                        <th>TOTAL</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                    <tr>
                                                        <th></th>
                                                        <th>MFO1</th>
                                                        <th>MFO2</th>
                                                        <th>MFO3</th>
                                                        <th></th>
                                                        <th>MFO4</th>
                                                        <th>MFO5</th>
                                                        <th></th>
                                                        <th>MFO4</th>
                                                        <th>MFO5</th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($mfosettings as $setting)
                                                        <tr>
                                                            <td>{{ $setting->category }}</td>
                                                            <td class="text-center">{{ $setting->core_mfo1 }}%</td>
                                                            <td class="text-center">{{ $setting->core_mfo2 }}%</td>
                                                            <td class="text-center">{{ $setting->core_mfo3 }}%</td>
                                                            <th class="text-center">{{ $setting->core_sum }}%</th>
                                                            <td class="text-center">{{ $setting->strategic_mfo4 }}%</td>
                                                            <td class="text-center">{{ $setting->strategic_mfo5 }}%</td>
                                                            <th class="text-center">{{ $setting->strat_sum }}%</th>
                                                            <td class="text-center">{{ $setting->support_mfo4 }}%</td>
                                                            <td class="text-center">{{ $setting->support_mfo5 }}%</td>
                                                            <th class="text-center">{{ $setting->support_sum }}%</th>
                                                            <th class="text-center">{{ $setting->core_sum + $setting->strat_sum + $setting->support_sum }}%</th>
                                                            <td class="text-center">
                                                                <a href="{{ route('mfoSettingsEdit', $setting->id) }}" class="btn btn-info btn-xs">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <button value="{{ $setting->id }}" class="btn btn-danger btn-xs mfo-delete">
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
</div>
@endsection
