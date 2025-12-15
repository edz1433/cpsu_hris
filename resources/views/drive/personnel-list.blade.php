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
                         <li class="breadcrumb-item text-muted">{{ ucfirst($cat) }}</li>
                    </ol> 
                </nav>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3">
                            <div class="card card-muted card-outline">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-user"></i> {{ strtoupper($cat) }}
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <form class="form-horizontal" action="{{ $current_route == "spmsPersonnlist" ? route('spmsPersonnCreate') : route('spmsPersonnUpdate') }}" method="POST">
                                        @csrf
                                        <input type="text" name="cat" id="cat" value="{{ $cat }}" hidden>
                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-md-12">
                                                    <div class="input-group">
                                                        @if(isset($personnelsEdit))
                                                        <input type="hidden" name="person_id" id="person_id" class="form-control" value="{{ $personnelsEdit->id }}">
                                                        @endif
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-id-card"></i>
                                                            </span>
                                                        </div>
                                                        <select class="form-control form-control-sm select2" id="empid" name="empid" required>
                                                            <option value=""> --- Select Employee --- </option>
                                                            @foreach($employees as $emp)
                                                                <option value="{{ $emp->id }}" @if(isset($personnelsEdit) && $emp->id == $personnelsEdit->empid) selected @endif>{{ $emp->lname }} {{ $emp->fname }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        @if($cat == 'personnel')
                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-md-12">
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-list-alt"></i>
                                                            </span>
                                                        </div>
                                                        <select class="form-control form-control-sm select2" id="category" name="category" required>
                                                            <option value=""> --- Select Category --- </option>
                                                            <option value="2" @if(isset($personnelsEdit) && $personnelsEdit->category == 2) selected @endif>Dean</option>
                                                            <option value="3" @if(isset($personnelsEdit) && $personnelsEdit->category == 3) selected @endif>Campus Ad</option>
                                                            <option value="4" @if(isset($personnelsEdit) && $personnelsEdit->category == 4) selected @endif>Office Head</option>
                                                            <option value="5" @if(isset($personnelsEdit) && $personnelsEdit->category == 5) selected @endif>Director</option>
                                                            <option value="6" @if(isset($personnelsEdit) && $personnelsEdit->category == 6) selected @endif>Staff</option>
                                                            <option value="7" @if(isset($personnelsEdit) && $personnelsEdit->category == 7) selected @endif>Faculty</option>
                                                        </select>
                                                    </div>    
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                        @if($cat == 'pmt')
                                        <input type="hidden" name="category" id="category" value="1">
                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-md-12">
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-list-alt"></i>
                                                            </span>
                                                        </div>
                                                        <select class="form-control select2" id="position" name="position">
                                                            <option value=""> --- Select Position --- </option>
                                                            <option value="1" @if(isset($personnelsEdit) && $personnelsEdit->position == 1) selected @endif>Performance Management Team</option>
                                                            <option value="2" @if(isset($personnelsEdit) && $personnelsEdit->position == 2) selected @endif>Local PMT</option>
                                                            <option value="3" @if(isset($personnelsEdit) && $personnelsEdit->position == 3) selected @endif>PMT Secretariat</option>
                                                        </select>
                                                    </div>    
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        @if($cat == 'personnel')
                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-md-12">
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-list-alt"></i>
                                                            </span>
                                                        </div>
                                                        <select class="form-control form-control-sm select2" id="off_coll_id" name="off_coll_id">
                                                            <option value=""> --- Select Office/College --- </option>
                                                            @foreach ($officecolleges as $off)
                                                                <option value="{{ $off->id }}" @if(isset($personnelsEdit) && $personnelsEdit->off_coll_id == $off->id) selected @endif>{{ $off->office_name }}</option>
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
                                                                <i class="fas fa-list-alt"></i>
                                                            </span>
                                                        </div>
                                                        <select class="form-control form-control-sm select2" id="strat_function" name="strat_function">
                                                            <option value=""> --- Select Personnel Category --- </option>
                                                            @foreach ($stratfunctions as $personcat)
                                                                <option value="{{ $personcat->id }}" @if(isset($personnelsEdit) && $personnelsEdit->employee_strat_function == $personcat->id) selected @endif>{{ $personcat->category }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @if($cat == 'personnel')
                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-md-12">
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-pencil-alt"></i>
                                                            </span>
                                                        </div>
                                                        <input type="text" class="form-control form-control-sm" id="emp_position" name="emp_position" placeholder="Enter Position" value="{{ isset($personnelsEdit) ? $personnelsEdit->emp_position : '' }}" autocomplete="off">
                                                    </div>    
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                        <div class="form-group">
                                            <div class="form-row">
                                                <div class="col-md-12">
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-pencil-alt"></i>
                                                            </span>
                                                        </div>
                                                        <input type="text" class="form-control form-control-sm" id="designation" name="designation" placeholder="Enter Designation" value="{{ isset($personnelsEdit) ? $personnelsEdit->designation : '' }}" autocomplete="off">
                                                    </div>    
                                                </div>
                                            </div>
                                        </div>
                                        
                                        @endif
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
                                        @php
                                            $groupedPersonnels = $personnels->groupBy('empid');
                                        @endphp
                                        <table id="example1" class="table table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Full Name</th>
                                                    @if($cat == 'personnel')
                                                        <th>Category</th>
                                                        <th>Office/College</th>
                                                        <th>Position</th>
                                                        <th>Designation</th>
                                                        <th>Strategic Function</th>
                                                    @else
                                                        <th>Position</th>
                                                    @endif
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody">
                                                @foreach($groupedPersonnels as $empid => $records)
                                                    @php
                                                        $first = $records->first();

                                                        $categories = $records->map(function($r) {
                                                            return match ($r->category) {
                                                                1 => 'PMT',
                                                                2 => 'DEANS',
                                                                3 => 'CAMPUS ADMINISTRATOR',
                                                                4 => 'OFFICE HEAD',
                                                                5 => 'DIRECTOR',
                                                                6 => 'STAFF',
                                                                7 => 'FACULTY',
                                                                default => 'N/A',
                                                            };
                                                            
                                                        })->unique()->map(fn($cat) => "<div style='margin-bottom: 4px;'>$cat</div>")->implode('');

                                                        $offices = $records->map(function($r) use ($officecolleges) {
                                                            if ($r->off_coll_id) {
                                                                $office = $officecolleges->firstWhere('id', $r->off_coll_id);
                                                                return $office ? $office->office_abbr : 'N/A';
                                                            }
                                                            return 'N/A';
                                                        })->unique()->map(fn($abbr) => "<div style='margin-bottom: 4px;'>$abbr</div>")->implode('');

                                                        $position = match((int) $first->position) {
                                                            1 => 'Performance Management Team',
                                                            2 => 'Local PMT',
                                                            3 => 'PMT Secretariat',
                                                            default => 'N/A',
                                                        };
                                                    @endphp

                                                    <tr id="tr-{{ $first->pmtid }}">
                                                        <td>{{ $first->lname }} {{ $first->fname }}</td>

                                                        @if($cat == 'personnel')
                                                            <td>{!! $categories !!}</td>
                                                        @endif

                                                        @if($cat == 'pmt')
                                                            <td>{{ $position }}</td>
                                                        @endif

                                                        @if($cat == 'personnel')
                                                            <td>{!! $offices !!}</td>
                                                            <td>{{ $first->emp_position }}</td>
                                                            <td>{{ isset($first->designation) ? strtoupper($first->designation) : 'N/A' }}</td>
                                                            {{-- Now sourced from employees table --}}
                                                            <td>{{ $first->strat_category ?? 'N/A' }}</td>
                                                        @endif

                                                        <td class="text-center">
                                                            @foreach($records as $record)
                                                                <div class="mb-1">
                                                                    <a href="{{ route('spmsPersonnEdit', ['cat' => ($cat == 'pmt') ? 'pmt' : 'personnel', 'id' => $record->personid]) }}" 
                                                                        class="btn btn-info btn-sm p-1" 
                                                                        style="font-size: 8px; line-height: 0.6;">
                                                                        <i class="fas fa-exclamation-circle fa-xs" style="margin-top: 11px;"></i>
                                                                    </a>
                                                                    
                                                                    <button value="{{ $record->personid }}" 
                                                                            class="btn btn-danger btn-sm p-1 person-delete" 
                                                                            style="font-size: 8px; line-height: 0.6;">
                                                                        <i class="fas fa-trash fa-xs"></i>
                                                                    </button>
                                                                </div>
                                                            @endforeach
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>                                        
                                        <div class="mb-3 d-flex gap-4 align-items-center">
                                            <div class="text-success">
                                                <i class="fas fa-check-circle"></i>
                                                <strong>{{ $stratFunctionHasCount }}</strong> 
                                            </div>
                                            <div class="text-danger ml-3">
                                                <i class="fas fa-times-circle"></i>
                                                <strong>{{ $stratFunctionNoneCount }}</strong>
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
</div>
@endsection
