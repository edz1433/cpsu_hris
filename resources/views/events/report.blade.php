@extends('layouts.master')

@section('body')
<div class="container-fluid">
  <div class="row">
    <div class="col-md-3">
      <div class="sticky-top mb-3">
        <div class="card">
          <div class="card-header">
            <div class="btn-group btn-block">
                <a href="{{ route('eventIndex') }}" class="btn bg-secondary "><i class="fas fa-calendar"></i> EVENT</a>
                <a href="{{ route('showReport') }}" class="btn bg-success1 "><i class="fas fa-file-pdf"></i> REPORTS</a>
            </div>
          </div>
          <div class="card-body">
           
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-9">
        <div class="card card-primary">
            <form class="form-horizontal add-form p-2" action="{{ route('dtrSearch') }}" method="POST">
                @csrf
                <div class="form-group row mtop">
                    <div class="col-md-3 col-sm-12">
                        <label class="badge badge-secondary lbel">Events</label><br>
                        <select class="form-control form-control-sm select2" name="employee" id="employee" required>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" @if(isset($selectedEvent) && $selectedEvent && $event->id == $selectedEvent->id) selected @endif>
                                    {{ ucfirst($event->title) }}
                                </option>
                            @endforeach
                        </select>                                    
                    </div>
                    <div class="col-md-3 col-sm-12">
                        <label class="badge badge-secondary lbel">Campus</label><br>
                        <select class="form-control form-control-sm select2 update-field" style="width: 100%;" name="camp_id" required>
                            <option value="0">All</option>
                            @foreach ($campus as $cp)
                                <option value="{{ $cp->id }}" data-column-name="camp_id" >{{ $cp->campus_name }}</option>
                            @endforeach
                        </select>
                    </div> 
                    <div class="col-md-3 col-sm-12">
                        <label class="badge badge-secondary lbel">Employee Status</label><br>
                        <select class="form-control form-control-sm select2 update-field" style="width: 100%;" name="emp_status" required>
                            <option value=""> select </option>
                            <option value="0">All</option>
                            @foreach ($status as $st)
                                <option value="{{ $st->id }}" data-column-name="emp_status">{{ $st->status_name }}</option>
                            @endforeach
                        </select>                                    
                    </div> 
                    <div class="col-md-3 col-sm-12">
                        <button class="btn btn-success btn-sm btn-block" style="margin-top: 21px;"><i class="fas fa-file-pdf"></i> Generate</button>                              
                    </div> 
                </div>
            </form>
            <iframe class="m-2" src="{{ route('reportGenrate') }}" width="98.5%" height="800px"></iframe>
        </div>
    </div>
  </div>
  <!-- /.row -->
</div><!-- /.container-fluid -->
            
@endsection