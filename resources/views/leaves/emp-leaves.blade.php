@extends('layouts.master')

@section('body')
@include('emp.style')
<section class="content">
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-3">
            <div class="card card-info card-outline">
                <div class="card-body box-profile">
                    <div class="text-center position-relative">
                        <div class="profile-image-container">
                            <img src="{{ asset('Profile/Employee/'.$employee->profile) }}" alt="User Image" class="profile-user-img img-fluid" id="changeProfilePicture">
                        </div>
                        <input type="file" id="profilePictureInput" style="display: none;" accept="image/*">
                    </div>
                    
                    <h3 class="profile-username text-center">{{ ucwords(strtolower($employee->fname)) }} {{ ucwords(strtolower($employee->lname)) }}</h3>
                    <p class="text-muted text-center">{{ $employee->position }}</p>
            
                    <ul class="list-group list-group-unbordered custom-gap">
                        <li class="list-group-item">
                            <b>Sick Leave</b> <span class="float-right  badge badge-success">{{ $employee->sl }}</span>
                        </li>
                        <li class="list-group-item">
                            <b>Vacation Leave</b> <span class="float-right  badge badge-success">{{ $employee->vl }}</span>
                        </li>
                        <li class="list-group-item">
                            <b>Special Privilege Leave</b> <span class="float-right  badge badge-success">{{ $employee->special_pl }}</span>
                        </li>
                        <li class="list-group-item">
                            <b>Solo Parent Leave</b> <span class="float-right  badge badge-success">{{ $employee->solo_pl }}</span>
                        </li>
                        <li class="list-group-item">
                            <b>AWOL</b> <span class="float-right  badge badge-success">{{ $employee->awol }}</span>
                        </li>
                    </ul>
                </div>
                <!-- /.card-body -->
            </div>
            <div class="card card-info">
                <div class="card-footer p-0">
                    <ul class="nav flex-column">
                        <li class="nav-item bg-secondary" style="margin-bottom: 5px; border-radius: 5px;">
                            <a href="{{ ($guard == 'web') ? route('PDS', $employee->id) : route('empPDS') }}" class="nav-link">
                                <i class="{{ request()->is('pds/personal-info/*')}} pr-2 fas fa-user" style="width: 20px; margin-left: 3px;"></i> 
                                <span class="{{ request()->is('pds/personal-info/*') }} text-light text-bold">PENDING</span> 
                                <span class="float-right pt-1 badge badge-light">5</span>
                            </a>
                        </li>
                        <li class="nav-item bg-success" style="margin-bottom: 5px; border-radius: 5px;">
                            <a href="{{ ($guard == 'web') ? route('PDS', $employee->id) : route('empPDS') }}" class="nav-link">
                                <i class="{{ request()->is('pds/personal-info/*')}} pr-2 fas fa-user" style="width: 20px; margin-left: 3px;"></i> 
                                <span class="{{ request()->is('pds/personal-info/*') }} text-light text-bold">APPROVED</span> 
                                <span class="float-right pt-1 badge badge-light">10</span>
                            </a>
                        </li>
                        <li class="nav-item bg-danger" style="margin-bottom: 5px; border-radius: 5px;">
                            <a href="{{ ($guard == 'web') ? route('PDS', $employee->id) : route('empPDS') }}" class="nav-link">
                                <i class="{{ request()->is('pds/personal-info/*')}} pr-2 fas fa-user" style="width: 20px; margin-left: 3px;"></i> 
                                <span class="{{ request()->is('pds/personal-info/*') }} text-light text-bold">DECLINED</span> 
                                <span class="float-right pt-1 badge badge-light">15</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h2 class="card-title text-success1">
                        <b>LEAVE CREDITS</b>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="form-row lbel">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-12 text-center my-4">
                                    <div class="card bg-light shadow-sm p-4">
                                        <h2 class="text-warning font-weight-bold text-center">Input Leave Credit Balance to Start</h2>
                                        <p class="text-muted text-center">Please enter employee leave credit balance below to proceed.</p>
                                    
                                        <form class="form-horizontal" action="{{ route('leavesCreate') }}" method="POST">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-3 col-sm-4 mb-3">
                                                    
                                                </div>
                                                <div class="col-md-3 col-sm-4 mb-3">
                                                    <div class="form-check text-center">
                                                        <label class="badge badge-secondary w-100">Sick Leave</label>
                                                        <input class="form-control form-control-sm" type="number" step="0.01" min="0" placeholder="0.00" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 col-sm-4 mb-3">
                                                    <div class="form-check text-center">
                                                        <label class="badge badge-secondary w-100">Vacation Leave</label>
                                                        <input class="form-control form-control-sm" type="number" step="0.01" min="0" placeholder="0.00" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 col-sm-4 mb-3">

                                                </div>
                                                <div class="col-md-6">
                                                </div>
                                                <div class="col-md-3 text-right">
                                                    <button type="submit" name="btn-submit" class="btn btn-success btn-sm">
                                                        <i class="fas fa-save"></i> Save
                                                    </button>
                                                </div>
                                                <div class="col-md-3">
                 
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-row">
                                                   
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>                                                             
                                <div class="col-3">
                       
                                </div>
                            </div>
                        </div>
                    </div>    
                    {{-- <div class="table-responsive mt-5">
                        <table class="table table-collapsed table-hover" id="example1">
                            <thead>
                                <tr>
                                    <th>NO.</th>
                                    <th>Days</th>
                                    <th>Earned</th>
                                    <th>Date</th>
                                </tr>
                            </thead> 
                        </table>                    
                    </div> --}}
            </div>                        
        </div>
    </div>
</div>
</section>
@endsection