@extends('layouts.master')

@section('body')
@php
    $current_route = request()->route()->getName();
@endphp
<div class="container-fluid">
    <div class="row">
        {{-- LEFT COLUMN (Add/Edit Job Form) --}}
        <div class="col-lg-3">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-briefcase"></i> {{ $current_route == "jlist" ? "Add Job" : "Edit Job" }}
                    </h3>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" 
                          action="{{ $current_route == "jlist" ? route('jCreate') : route('jUpdate') }}" 
                          method="POST">
                        @csrf
                        <input type="hidden" name="id" value="{{ $current_route == 'jEdit' ? $jEdit->id : '' }}">

                        {{-- Job Title --}}
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                                </div>
                                <input type="text" name="title" 
                                       value="{{ $current_route == 'jEdit' ? $jEdit->title : '' }}" 
                                       placeholder="Enter Job Title" 
                                       class="form-control form-control-sm" required>
                            </div>
                        </div>

                        {{-- Plantilla Item No. --}}
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                </div>
                                <input type="text" name="plantilla_item_no" 
                                       value="{{ $current_route == 'jEdit' ? $jEdit->plantilla_item_no : '' }}" 
                                       placeholder="Enter Plantilla Item No." 
                                       class="form-control form-control-sm" required>
                            </div>
                        </div>

                        {{-- Salary --}}
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                                </div>
                                <input type="number" step="0.01" name="salary" 
                                       value="{{ $current_route == 'jEdit' ? $jEdit->salary : '' }}" 
                                       placeholder="Enter Salary" 
                                       class="form-control form-control-sm" required>
                            </div>
                        </div>

                        {{-- Education --}}
                        <div class="form-group">
                            <textarea name="education" class="form-control form-control-sm" placeholder="Required Education">{{ $current_route == 'jEdit' ? $jEdit->education : '' }}</textarea>
                        </div>

                        {{-- Eligibility --}}
                        <div class="form-group">
                            <textarea name="eligibility" class="form-control form-control-sm" placeholder="Eligibility">{{ $current_route == 'jEdit' ? $jEdit->eligibility : '' }}</textarea>
                        </div>

                        {{-- Training --}}
                        <div class="form-group">
                            <textarea name="training" class="form-control form-control-sm" placeholder="Training (optional)">{{ $current_route == 'jEdit' ? $jEdit->training : '' }}</textarea>
                        </div>

                        {{-- Experience --}}
                        <div class="form-group">
                            <textarea name="experience" class="form-control form-control-sm" placeholder="Experience (optional)">{{ $current_route == 'jEdit' ? $jEdit->experience : '' }}</textarea>
                        </div>

                        {{-- Competency --}}
                        <div class="form-group">
                            <textarea name="competency" class="form-control form-control-sm" placeholder="Competency (optional)">{{ $current_route == 'jEdit' ? $jEdit->competency : '' }}</textarea>
                        </div>

                        {{-- Posted / Expiration --}}
                        <div class="form-group">
                            <label>Posted At</label>
                            <input type="date" name="posted_at" value="{{ $current_route == 'jEdit' ? $jEdit->posted_at : '' }}" class="form-control form-control-sm" required>
                        </div>
                        <div class="form-group">
                            <label>Expiration At</label>
                            <input type="date" name="expiration_at" value="{{ $current_route == 'jEdit' ? $jEdit->expiration_at : '' }}" class="form-control form-control-sm" required>
                        </div>

                        {{-- Save Button --}}
                        <div class="form-group">
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN (Job List) --}}
        <div class="col-lg-9">
            <div class="card card-info card-outline">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example1" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Position Title</th>
                                    <th>Plantilla No.</th>
                                    <th>Salary</th>
                                    <th>Education</th>
                                    <th>Eligibility</th>
                                    <th>Training</th>
                                    <th>Experience</th>
                                    <th>Competency</th>
                                    <th>Posted</th>
                                    <th>Expiration</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="tbody">
                                @php $no = 1; @endphp
                                @foreach($jobs as $job)
                                <tr id="tr-{{ $job->id }}">
                                    <td>{{ $no++ }}</td>
                                    <td>{{ $job->title }}</td>
                                    <td>{{ $job->plantilla_item_no }}</td>
                                    <td>{{ number_format($job->salary, 2) }}</td>
                                    <td>{{ $job->education }}</td>
                                    <td>{{ $job->eligibility }}</td>
                                    <td>{{ $job->training ?? '-' }}</td>
                                    <td>{{ $job->experience ?? '-' }}</td>
                                    <td>{{ $job->competency ?? '-' }}</td>
                                    <td>{{ $job->posted_at }}</td>
                                    <td>{{ $job->expiration_at }}</td>
                                    <td>
                                        <span class="badge {{ $job->status == 'Open' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $job->status }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('jEdit', $job->id) }}" class="btn btn-info btn-xs">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button value="{{ $job->id }}" class="btn btn-danger btn-xs job-delete">
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
@endsection
