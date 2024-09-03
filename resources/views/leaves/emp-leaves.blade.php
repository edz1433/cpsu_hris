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
                                <i class="pr-2 fas fa-clock" style="width: 20px; margin-left: 3px;"></i> 
                                <span class="{{ request()->is('pds/personal-info/*') ? 'text-light text-bold' : '' }}">PENDING</span> 
                                <span class="float-right pt-1 badge badge-light">5</span>
                            </a>
                        </li>
                        <li class="nav-item bg-success" style="margin-bottom: 5px; border-radius: 5px;">
                            <a href="{{ ($guard == 'web') ? route('PDS', $employee->id) : route('empPDS') }}" class="nav-link">
                                <i class="pr-2 fas fa-check-circle text-light" style="width: 20px; margin-left: 3px;"></i> 
                                <span class="{{ request()->is('pds/personal-info/*') ? 'text-light text-bold' : '' }}">APPROVED</span> 
                                <span class="float-right pt-1 badge badge-light">10</span>
                            </a>
                        </li>
                        <li class="nav-item bg-danger" style="margin-bottom: 5px; border-radius: 5px;">
                            <a href="{{ ($guard == 'web') ? route('PDS', $employee->id) : route('empPDS') }}" class="nav-link">
                                <i class="pr-2 fas fa-times-circle" style="width: 20px; margin-left: 3px;"></i> 
                                <span class="{{ request()->is('pds/personal-info/*') ? 'text-light text-bold' : '' }}">DECLINED</span> 
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
                    <button type="button" class="btn btn-info btn-sm float-right" data-toggle="modal" data-target="#leaveModal">
                        <i class="fas fa-plus"></i> 
                    </button>
                </div>
                <div class="card-body">
                    @if(count($leaves) == 0)
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
                                                <div class="col-md-3 col-sm-4 mb-3"></div>
                                        
                                                <div class="col-md-3 col-sm-4 mb-3">
                                                    <div class="form-check text-center">
                                                        <label class="badge badge-secondary w-100">Sick Leave</label>
                                                        <input type="hidden" name="empid" value="{{ $employee->id }}">
                                                        <input class="form-control form-control-sm" type="number" name="sl" step="0.01" min="0" max="{{ (count($leaves) == 0) ? '' : 30 }}" placeholder="0.00" required>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-3 col-sm-4 mb-3">
                                                    <div class="form-check text-center">
                                                        <label class="badge badge-secondary w-100">Vacation Leave</label>
                                                        <input class="form-control form-control-sm" type="number" name="vl" step="0.01" min="0" max="{{ (count($leaves) == 0) ? '' : 30 }}" placeholder="0.00" required>
                                                    </div>
                                                </div>
                                        
                                                <div class="col-md-3 col-sm-4 mb-3"></div>
                                        
                                                <div class="col-md-6"></div>
                                        
                                                <div class="col-md-3 text-right">
                                                    <button type="submit" name="btn-submit" class="btn btn-success btn-sm">
                                                        <i class="fas fa-save"></i> submit
                                                    </button>
                                                </div>
                                        
                                                <div class="col-md-3"></div>
                                            </div>
                                        </form>
                                    </div>
                                </div>                                                             
                                <div class="col-3">
                       
                                </div>
                            </div>
                        </div>
                    </div>    
                    @else
                    <div class="table-responsive ">
                        <table class="table table-collapsed table-hover" id="example1">
                            <thead>
                                <tr>
                                    <th>Days</th>
                                    <th>SL Earned</th>
                                    <th>VL Earned</th>
                                    <th>Date</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead> 
                            @php
                                $totalLeaves = $leaves->count();
                            @endphp
                            <tbody>
                                @foreach($leaves as $index => $leave)
                                    <tr>
                                        <td class="text-center">{{ $leave->days }}</td>
                                        <td class="text-center">{{ $leave->earn_sl }}</td>
                                        <td class="text-center">{{ $leave->earn_vl }}</td>
                                        <td class="text-center">
                                            {{ \Carbon\Carbon::parse($leave->date)->format('F Y') }}
                                            @if ($index === $totalLeaves - 1)
                                                <br><span class="badge badge-warning">(starting Balance)</span>
                                            @endif
                                        </td>
                                        <td  width="100" class="text-center">
                                            <a href="#" class="btn btn-info btn-sm mb-2" title="Edit">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            @if ($index !== 0)
                                            <button class="btn btn-danger btn-sm mb-2 eligible_delete" value="{{ $leave->id }}" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>                    
                    </div>
                    @endif
            </div>                        
        </div>
    </div>
</div>
</section>
<div class="modal fade" id="leaveModal" tabindex="-1" role="dialog" aria-labelledby="leaveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leaveModalLabel">Leave Form</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" action="{{ route('leavesCreate') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-check text-center">
                                <label class="badge badge-secondary w-100">Date</label>
                                <input class="form-control form-control-sm" type="month" id="date" name="date" required>
                            </div>
                        </div>
            
                        <div class="col-md-3">
                            <div class="form-check text-center">
                                <label class="badge badge-secondary w-100">Days</label>
                                <input type="hidden" name="empid" value="{{ $employee->id }}">
                                <input class="form-control form-control-sm" type="number" id="days" name="days" min="1" max="30" oninput="updateEquivalent()" required>
                            </div>
                        </div>
            
                        <div class="col-md-3">
                            <div class="form-check text-center">
                                <label class="badge badge-secondary w-100">Sick Leave</label>
                                <input type="hidden" name="empid" value="{{ $employee->id }}">
                                <input class="form-control form-control-sm" type="text" id="sl" name="sl" step="0.01" min="0" max="30" placeholder="0.00" required readonly>
                            </div>
                        </div>
            
                        <div class="col-md-3">
                            <div class="form-check text-center">
                                <label class="badge badge-secondary w-100">Vacation Leave</label>
                                <input class="form-control form-control-sm" type="number" id="vl" name="vl" step="0.01" min="0" max="30" placeholder="0.00" required readonly>
                            </div>
                        </div>
            
                        <div class="col-md-12 text-right mt-3">
                            <button type="submit" name="btn-submit" class="btn btn-success btn-sm">
                                <i class="fas fa-save"></i> Submit
                            </button>
                        </div>
                    </div>
                </form>            
            </div>
        </div>
    </div>
</div>
<script>
    const equivalences = [
        0.042, 0.083, 0.125, 0.167, 0.208, 0.250, 0.292, 0.333, 0.375,
        0.417, 0.458, 0.500, 0.542, 0.583, 0.625, 0.667, 0.708, 0.750,
        0.792, 0.833, 0.875, 0.917, 0.958, 1.000, 1.042, 1.083, 1.125,
        1.167, 1.208, 1.250
    ];

    function updateEquivalent() {
        let daysInput = parseInt(document.getElementById('days').value, 10);
        const sl = document.getElementById('sl');
        const vl = document.getElementById('vl'); 

        if (isNaN(daysInput) || daysInput < 1) {
            sl.value = '';
            vl.value = '';
            return;
        }

        if (daysInput > 30) {
            daysInput = 30;
            document.getElementById('days').value = 30;
        }

        if (daysInput >= 1 && daysInput <= 30) {
            const equivalentValue = equivalences[daysInput - 1];
            sl.value = equivalentValue.toFixed(3);
            vl.value = equivalentValue.toFixed(3);
        } else {
            sl.value = '';
            vl.value = ''; 
        }
    }
</script>

@endsection