@extends('layouts.master')

@section('body')
<div class="container-fluid">
  <div class="row">
    <div class="col-md-3">
      <div class="sticky-top mb-3">
        <div class="card">
          <div class="card-header">
            <div class="btn-group btn-block">
                <a href="{{ route('eventIndex') }}" class="btn bg-success1 "><i class="fas fa-calendar"></i> EVENT</a>
                <a href="{{ route('showReport') }}" class="btn btn-secondary "><i class="fas fa-file-pdf"></i> REPORTS</a>
            </div>
          </div>
          <div class="card-body">
            <div class="btn-group" style="width: 100%; margin-bottom: 10px;">
              <ul class="fc-color-picker" id="color-chooser">
                <li><a class="text-primary" href="#" data-color="bg-primary" onclick="showColor(this)"><i class="fas fa-square"></i></a></li>
                <li><a class="text-info" href="#" data-color="bg-info" onclick="showColor(this)"><i class="fas fa-square"></i></a></li>
                <li><a class="text-warning" href="#" data-color="bg-warning" onclick="showColor(this)"><i class="fas fa-square"></i></a></li>
                <li><a class="text-success" href="#" data-color="bg-success" onclick="showColor(this)"><i class="fas fa-square"></i></a></li>
                <li><a class="text-danger" href="#" data-color="bg-danger" onclick="showColor(this)"><i class="fas fa-square"></i></a></li>
                <li><a class="text-muted" href="#" data-color="bg-secondary" onclick="showColor(this)"><i class="fas fa-square"></i></a></li>
              </ul>
            </div>
        
            <form action="{{ route('eventCreate') }}" method="POST">
              @csrf
              <input type="hidden" name="user_id" value="{{ Auth::user()->id }}">
              
              <div class="form-group">
                <label for="eventTitle">Event Title</label>
                <input type="text" class="form-control form-control-sm" id="eventTitle" name="title" required>
              </div>

              <div class="form-group">
                <label for="eventTitle">Venue</label>
                <input type="text" class="form-control form-control-sm" id="eventTitle" name="venue" required>
              </div>

              <div class="form-group">
                <label for="eventTitle">Organizing Department/s</label>
                <input type="text" class="form-control form-control-sm" id="org_dept" name="org_dept" required>
              </div>

              <div class="form-group">
                <label for="Campus">Campus</label><br>
                <select class="form-control form-control-sm select2 update-field" style="width: 100%;" name="campus_id" required>
                    <option disabled selected> select </option>
                    <option value="0">All</option>
                    @foreach ($campus as $cp)
                        <option value="{{ $cp->id }}" data-column-name="camp_id">{{ $cp->campus_name }}</option>
                    @endforeach
                </select>
              </div>

              <div class="form-group">
                  <label for="Emmployee Status">Employee Status</label><br>
                  <select class="form-control form-control-sm select2 update-field" style="width: 100%;" name="emp_status" required>
                      <option value=""> select </option>
                      <option value="0">All</option>
                      @foreach ($status as $st)
                          <option value="{{ $st->id }}" data-column-name="emp_status">{{ $st->status_name }}</option>
                      @endforeach
                  </select>                                    
              </div> 
              
              <div class="form-group">
                <label for="eventStartTime">Start Time</label>
                <input type="datetime-local" class="form-control form-control-sm" id="eventStartTime" name="start" required>
              </div>
            
              <div class="form-group">
                <label for="eventEndTime">End Time</label>
                <input type="datetime-local" class="form-control form-control-sm" id="eventEndTime" name="end">
              </div>
            
              <input type="hidden" class="form-control form-control-sm" id="bg_color" name="bg_color" value="bg-primary">
            
              <div class="form-group mt-2">
                <button type="submit" id="submit-bg" class="btn btn-primary btn-sm">Save Event</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <!-- /.col -->
    <div class="col-md-9">
      <div class="card card-primary">
        <div class="card-body p-0">
          <div id="external-events">
            <div class="row m-2">
              <div class="col-2">
                <div class="external-event bg-muted"><i class="fas fa-square mr-2 text-primary"></i>  Academic Council</div>
              </div>
              <div class="col-2">
                <div class="external-event bg-muted"><i class="fas fa-square mr-2 text-info"></i>Admin Council</div>
              </div>
              <div class="col-2">
                <div class="external-event bg-muted"><i class="fas fa-square mr-2 text-warning"></i> Convocation</div>
              </div>
              <div class="col-2">
                <div class="external-event bg-muted"><i class="fas fa-square mr-2 text-success"></i> Trainings & Seminar</div>
              </div>
              <div class="col-2">
                <div class="external-event bg-muted"><i class="fas fa-square mr-2 text-danger"></i>Orientation</div>
              </div>
              <div class="col-2">
                <div class="external-event bg-muted"><i class="fas fa-square mr-2 text-primary"></i>Orientation</div>
              </div>
            </div>
            <hr>
          </div>
          <div id="calendar"></div>
        </div>
        <!-- /.card-body -->
      </div>
      <!-- /.card -->
    </div>
    <!-- /.col -->
  </div>
  <!-- /.row -->
</div><!-- /.container-fluid -->
            
@endsection