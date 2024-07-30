@extends('layouts.master')

@section('body')
<div class="container-fluid">
    <div class="row" style="padding-top: 10px;">
        @if($guard == "web")
        <div class="col-2">
            @include('dtr.submenu')
        </div>
        @endif
        <div class="col-lg-{{ ($guard == "web") ? '10' : '12' }}">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h2 class="card-title text-success1">
                        <b>DAILY TIME RECORD</b>
                    </h2>
                </div>
                <div class="card-body">
                    <form class="form-horizontal add-form" action="{{ route('dtrSearch') }}" method="POST">
                        @csrf
                        <div class="form-group mtop">
                            <div class="form-row">
                                <div class="col-md-3 col-sm-12">
                                    <label class="badge badge-secondary lbel">Employee Name</label><br>
                                    <select class="form-control form-control-sm {{ (auth()->guard($guard)->user()->role == "employee") ? '' : 'select2' }}" name="employee" id="employee"  @if(auth()->guard($guard)->user()->role == "employee") style="pointer-events: none;" @endif required>
                                        <option disabled selected>Select</option>
                                        @if(auth()->guard($guard)->user()->role !== "employee")
                                            @foreach($employeeall as $emp)
                                                <option value="{{ $emp->emp_ID }}" @if(isset($employee) && $employee && $emp->emp_ID == $employee->emp_ID) selected @endif>
                                                    {{ strtoupper(ucwords($emp->lname)) }}
                                                    {{ strtoupper(ucwords($emp->prefix)) }}
                                                    {{ strtoupper(ucwords($emp->fname)) }}
                                                    {{ strtoupper(ucwords($emp->mname)) }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="{{ $employeeall->emp_ID }}" selected>
                                                {{ strtoupper(ucwords($employeeall->lname)) }}
                                                {{ strtoupper(ucwords($employeeall->prefix)) }}
                                                {{ strtoupper(ucwords($employeeall->fname)) }}
                                                {{ strtoupper(ucwords($employeeall->mname)) }}
                                            </option>
                                        @endif
                                    </select>                                    
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="badge badge-secondary lbel">Period</label><br>
                                    <select class="form-control form-control-sm" name="period" required>
                                        <option value="1" @if(isset($employee) && $period == 1) selected @endif>1st half</option>
                                        <option value="2" @if(isset($employee) && $period == 2) selected @endif>2nd half</option>
                                        <option value="3" @if(isset($employee) && $period == 3) selected @endif>Whole Month</option>
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <label class="badge badge-secondary lbel">Month</label>
                                    <input type="month" name="date" class="form-control form-control-sm" id="date" value="{{ isset($employee) ? $date : '' }}" required>
                                </div>
                                <div class="col-md-1 col-sm-6 d-flex align-items-center">
                                    <div>
                                        <label class="badge badge-secondary lbel d-block">Overtime</label>
                                        <input type="checkbox" value="1" name="overtime" class="form-control form-control-sm" style="margin-top: 9px;" {{ isset($employee) && $overtime == 1 ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-6 d-flex align-items-end">
                                    <button class="btn btn-success btn-sm btn-block"><i class="fas fa-file-pdf"></i> Generate</button>
                                </div>
                            </div>
                        </div>                        
                    </form>
                    <iframe 
                    src="{{ isset($employee, $period, $date) ? route('dtr-pdf', ['employee' => $employee->emp_ID, 'period' => $period, 'date' => $date, 'overtime' => $overtime]) : '' }}" width="100%" height="600px"></iframe>
                 </div>
            </div>
        </div>
    </div>
</div>
<script>
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };
</script>
@endsection