@extends('layouts.master')

@section('body')
<style>
    .bg-white {
        border-radius: 25px;
    }
    .icon{
        position: absolute;
        top: 37px !important;
        right: 5px;
    }
    .border-radius{
      border-radius: 8px !important;
      width: 40px !important;
      height: 40px !important;
    } 
</style>
@include('home.modal')
<div class="container-fluid">
    <div class="wrapper">
        <section class="content">
            <div class="row">
                <div class="col-lg-8 col-sm-12">
                  <div class="row">
                    <div class="col-12">
                      <div class="row">
                          <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <div class="small-box pl-1 pt-2 bg-white">
                                  <div class="inner">
                                      <h6 class="text-gray">Employee</h6>
                                      <h3 class="">{{ number_format($empCount) }}</h3>
                                  </div>
                                  <div class="icon">
                                      <i class="fa-solid fa-user-group" style="color: #51A9F8; font-size: 30px !important;"></i>
                                  </div>
                              </div>
                          </div>
                          <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <div class="small-box pl-1 pt-2 bg-white">
                                  <div class="inner">
                                      <h6 class="text-muted">Present</h6>
                                      <h3>0</h3>
                                  </div>
                                  <div class="icon">
                                      <i class="fa-solid fa-users-viewfinder" style="color: #D2BD90; font-size: 30px !important;"></i>
                                  </div>
                              </div>
                          </div>
                          <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <div class="small-box pl-1 pt-2 bg-white">
                                  <div class="inner">
                                      <h6 class="text-muted">Late</h6>
                                      <h3>0</h3>
                                  </div>
                                  <div class="icon">
                                      <i class="fas fa-clock" style="color: #E6AB83; font-size: 30px !important;"></i>
                                  </div>
                              </div>
                          </div>
                          <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <a href="{{ route('readTiredness'); }}">
                                <div class="small-box pl-1 pt-2 bg-white">
                                    <div class="inner">
                                        <h6 class="text-muted">Tiredness & Undertime</h6>
                                        <h3>0</h3>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-bed"  style="color: #DC7DF8;  font-size: 30px !important;"></i>
                                    </div>
                                </div>
                              </a>
                          </div>
                      </div>
                    </div>
                    <div class="col-12">
                        <div class="card  p-0">
                            <div class="card-body" style="background-color: #e9ecef;">
                                <!-- THE CALENDAR -->
                                <div id="calendar" class="bg-white"></div>
                            </div>
                        </div>
                    </div>
                  </div>
                </div>
                <div class="col-lg-4 col-sm-6">
                  <div class="row">
                    <div class="col-12">
                      <div class="card">
                        <div class="card-header">
                          <h3 class="card-title"><b>Employee Status</b></h3>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body p-0">
                          <table class="table table-sm">
                            <tbody>
                              <tr>
                                  <td>Regular</td>
                                  <td width="100">
                                      <div class="progress progress-xs mt-2">
                                          <div class="progress-bar bg-danger" style="width: {{ number_format($empStatusPercentages->get(1)['percentage'], 2) }}%"></div>
                                      </div>
                                  </td>
                                  <td>
                                      <span class="badge bg-danger">
                                          {{ number_format($empStatusPercentages->get(1)['percentage'], 2) . '%' }} 
                                      </span>  
                                      <span class="badge badge-secondary float-right">{{ $empStatusPercentages->get(1)['count'] }}</span>
                                  </td>
                              </tr>
                              <tr>
                                  <td>Full-time / Part-time</td>
                                  <td width="100">
                                      <div class="progress progress-xs mt-2">
                                          <div class="progress-bar bg-warning" style="width: {{ number_format($empStatusPercentages->get(2)['percentage'], 2) }}%"></div>
                                      </div>
                                  </td>
                                  <td>
                                      <span class="badge bg-warning">
                                          {{ number_format($empStatusPercentages->get(2)['percentage'], 2) . '%' }}
                                      </span>  
                                      <span class="badge badge-secondary float-right">{{ $empStatusPercentages->get(2)['count'] }}</span>
                                  </td>
                              </tr>
                              <tr>
                                  <td>Part-time / Part-time</td>
                                  <td width="100">
                                      <div class="progress progress-xs mt-2 progress-striped active">
                                          <div class="progress-bar bg-primary" style="width: {{ number_format($empStatusPercentages->get(3)['percentage'], 2) }}%"></div>
                                      </div>
                                  </td>
                                  <td>
                                      <span class="badge bg-primary">
                                          {{ number_format($empStatusPercentages->get(3)['percentage'], 2) . '%' }}
                                      </span>
                                      <span class="badge badge-secondary float-right">{{ $empStatusPercentages->get(3)['count'] }}</span> 
                                  </td>
                              </tr>
                              <tr>
                                  <td>Job Order</td>
                                  <td width="100"> 
                                      <div class="progress progress-xs mt-2 progress-striped active">
                                          <div class="progress-bar bg-success" style="width: {{ number_format($empStatusPercentages->get(4)['percentage'], 2) }}%"></div>
                                      </div>
                                  </td>
                                  <td>
                                      <span class="badge bg-success">
                                          {{ number_format($empStatusPercentages->get(4)['percentage'], 2) . '%' }}  
                                      </span>  
                                      <span class="badge badge-secondary float-right">{{ $empStatusPercentages->get(4)['count'] }}</span>
                                  </td>
                              </tr>
                          </tbody>
                          
                          </table>
                        </div>
                        <!-- /.card-body -->
                      </div>
                    </div>
                    <div class="col-12">
                      <div class="card">
                        <div class="card-header">
                          <h3 class="card-title">Birthday</h3>
                          <div class="card-tools">
                            <input type="month" class="form-control form-control-sm" style="width: auto; display: inline-block;" id="monthInput">
                          </div>
                        </div>
                        <div class="card-body p-0">
                          <ul class="products-list product-list-in-card pl-2 pr-2">
                            @foreach($upcomingBirthdays as $employee)
                              <li class="item">
                                <div class="product-img">
                                    @php
                                        $imageUrl = asset('Profile/Employee/' . $employee->profile);
                                        $imagePath = public_path('Profile/Employee/' . $employee->profile);
                                    @endphp
                                    <img class="border-radius" src="{{ file_exists($imagePath) ? $imageUrl : asset('Profile/Employee/default.png') }}" alt="Product Image">
                                </div>
                                <div class="product-info">
                                  <a href="#" class="product-title text-dark">{{ ucfirst(strtolower($employee->lname)) . ' ' . ucfirst(strtolower($employee->fname)) }}
                                    <span class="float-right" style="margin-top: -2px;"><i class="fas fa-birthday-cake" style="color: #e71515;"></i></span>
                                  </a>
                                  <span class="product-description">
                                    MIS Staff <span class="float-right" style="margin-top: -2px;">{{ $employee->bdate->format('F j, Y') }}</span>
                                  </span>
                                </div>
                              </li>
                            @endforeach
                          </ul>
                        </div>
                      </div>
                    </div>                    
                  </div>
                </div>
                
            </div>
        </section>
    </div>
</div>
<script>
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };
</script>
<script>
  // Get the current date
  const currentDate = new Date();
  
  // Get the current month and year
  const currentMonth = currentDate.getMonth() + 1; // Months are zero-indexed
  const currentYear = currentDate.getFullYear();
  
  // Format the month to always have two digits
  const formattedMonth = currentMonth < 10 ? '0' + currentMonth : currentMonth;
  
  // Set the value of the input to the current month and year
  document.getElementById('monthInput').value = `${currentYear}-${formattedMonth}`;
  
  // Disable the year selection
  document.getElementById('monthInput').addEventListener('click', function() {
      this.showPicker = () => {};
  });
</script>
@endsection
