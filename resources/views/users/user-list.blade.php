@extends('layouts.master')

@section('body')
@php
    $current_route=request()->route()->getName();
@endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-3">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus"></i> {{ $current_route == "ulist" ? "Add" : "Edit" }}
                    </h3>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ $current_route == "ulist" ? route('uCreate') : route('uUpdate') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                        </div>
                                        <input type="hidden" name="uid" value="">
                                        <input type="text" name="lname" value="" oninput="this.value = this.value.toUpperCase()" placeholder="Enter Last Name" class="form-control form-control-sm" required="">
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
                                        <input type="text" name="fname" value="" oninput="this.value = this.value.toUpperCase()" placeholder="Enter First Name" class="form-control form-control-sm" required="">
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
                                        <input type="text" name="mname" value="" oninput="this.value = this.value.toUpperCase()" placeholder="Enter Middle Name" class="form-control form-control-sm" required="">
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
                                                <i class="fas fa-venus-mars"></i>
                                            </span>
                                        </div>
                                        <select name="gender" class="form-control form-control-sm" required="">
                                            <option value="">--- Select Gender ---</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
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
                                                <i class="fas fa-building"></i>
                                            </span>
                                        </div>
                                        <select class="form-control form-control-sm select2bs4" name="campus_id">
                                            <option value=""> --- Select Campus --- </option>
                                            @foreach ($camp as $cp)
                                                <option value="{{ $cp->id }}" @if($current_route == 'uEdit' && $cp->id == $uEdit->campus_id) selected @endif>{{ $cp->campus_name }}</option>
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
                                                <i class="fas fa-info-circle"></i>
                                            </span>
                                        </div>
                                        <select class="form-control form-control-sm select_camp" name="role" id="roleSelect" onchange="updateCheckboxes()">
                                            <option value=""> --- Select Role --- </option>
                                            <option value="Administrator">Administrator</option>
                                            <option value="HR Administrator">HR Administrator</option>
                                            <option value="Payroll Administrator">Payroll Administrator</option>
                                        </select>
                                    </div>
                                    <span id="error" style="color: #FF0000; font-size: 10pt;" class="form-text text-left Role_error"></span>
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
                                        <input type="text" name="username" value="" placeholder="Enter Username" class="form-control form-control-sm">
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
                                                <i class="fas fa-lock"></i>
                                            </span>
                                        </div>
                                        <input type="password" name="password" value="" placeholder="Enter Password" class="form-control form-control-sm">
                                    </div>    
                                </div>
                            </div>
                        </div>

                                                
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <label>Access Permissions:</label>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="access[0]" value="1" id="access0" class="form-check-input">
                                        <label for="access0" class="form-check-label">EMPLOYEES</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="access[1]" value="1" id="access1" class="form-check-input">
                                        <label for="access1" class="form-check-label">OFFICES</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="access[2]" value="1" id="access2" class="form-check-input">
                                        <label for="access2" class="form-check-label">PAYSLIP</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="access[3]" value="1" id="access3" class="form-check-input">
                                        <label for="access3" class="form-check-label">EVENTS</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="access[4]" value="1" id="access4" class="form-check-input">
                                        <label for="access4" class="form-check-label">DTR</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="access[5]" value="1" id="access5" class="form-check-input">
                                        <label for="access5" class="form-check-label">SPMS</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="access[7]" value="1" id="access6" class="form-check-input">
                                        <label for="access6" class="form-check-label">SETTINGS</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="access[7]" value="1" id="access7" class="form-check-input">
                                        <label for="access7" class="form-check-label">KIOSK</label>
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
            <div class="card card-info card-outline">
                @if($current_route == "uEdit")
                <div class="card-header">
                    <div class="col-md-12">
                        <ol class="breadcrumb float-md-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('ulist') }}">User</a></li>
                            <li class="breadcrumb-item">Edit</li>
                        </ol>                            
                    </div>
                </div>
                @endif
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example1" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Campus Name</th>
                                    <th>Last Name</th>
                                    <th>First Name</th>
                                    <th>Middle Name</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="tbody">
                                @php $no = 1; @endphp
                                @foreach($users as $user)
                                <tr id="tr-{{ $user->uid }}">
                                    <td>{{ $no++ }}</td>
                                    <td>{{ $user->campus_name }}</td>
                                    <td>{{ $user->lname }}</td>
                                    <td>{{ $user->fname }}</td>
                                    <td>{{ $user->mname }}</td>
                                    <td>{{ $user->username }}</td>
                                    <td>{{ $user->role }}</td>
                                    <td>
                                        <a href="{{ route('uEdit', $user->uid) }}" class="btn btn-info btn-xs">
                                            <i class="fas fa-exclamation-circle"></i>
                                        </a>
                                        <button value="{{ $user->uid }}" class="btn btn-danger btn-xs users-delete">
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