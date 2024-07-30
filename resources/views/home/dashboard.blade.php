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
                                      <h5 class="text-gray">Employee</h5>
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
                                      <h5 class="text-muted">Present</h5>
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
                                      <h5 class="text-muted">Late</h5>
                                      <h3>0</h3>
                                  </div>
                                  <div class="icon">
                                      <i class="fas fa-clock" style="color: #E6AB83; font-size: 30px !important;"></i>
                                  </div>
                              </div>
                          </div>
                          <div class="col-lg-3 col-6">
                              <!-- small box -->
                              <div class="small-box pl-1 pt-2 bg-white">
                                  <div class="inner">
                                      <h5 class="text-muted">Annual Leave</h5>
                                      <h3>0</h3>
                                  </div>
                                  <div class="icon">
                                      <i class="fa-regular fa-calendar-days"  style="color: #DC7DF8;  font-size: 30px !important;"></i>
                                  </div>
                              </div>
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
                                    <div class="progress-bar progress-bar-danger" style="width: 55%"></div>
                                  </div>
                                </td>
                                <td><span class="badge bg-danger">55%</span></td>
                              </tr>
                              <tr>
                                <td>Job Order</td>
                                <td width="100">
                                  <div class="progress progress-xs mt-2">
                                    <div class="progress-bar bg-warning" style="width: 70%"></div>
                                  </div>
                                </td>
                                <td><span class="badge bg-warning">70%</span></td>
                              </tr>
                              <tr>
                                <td>Full-time / Part-time</td>
                                <td width="100">
                                  <div class="progress progress-xs mt-2 progress-striped active">
                                    <div class="progress-bar bg-primary" style="width: 30%"></div>
                                  </div>
                                </td>
                                <td><span class="badge bg-primary">30%</span></td>
                              </tr>
                              <tr>
                                <td>Part-time / Part-time</td>
                                <td width="100"> 
                                  <div class="progress progress-xs mt-2 progress-striped active">
                                    <div class="progress-bar bg-success" style="width: 90%"></div>
                                  </div>
                                </td>
                                <td><span class="badge bg-success">90%</span></td>
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
                            <li class="item">
                              <div class="product-img">
                                <img class="border-radius" src="{{ asset('Employee/rosalie.jpg') }}" alt="Product Image">
                              </div>
                              <div class="product-info">
                                <a href="#" class="product-title text-dark">Gargoles Rosalie 
                                  <span class="float-right" style="margin-top: -2px;"><i class="fas fa-birthday-cake" style="color: #e71515;"></i></span>
                                </a>
                                <span class="product-description">
                                  MIS Staff <span class="float-right" style="margin-top: -2px;">July 5, 2024</span>
                                </span>
                                
                              </div>
                            </li>
                            <li class="item">
                              <div class="product-img">
                                <img class="border-radius" src="{{ asset('Employee/edwin.jpg') }}" alt="Product Image">
                              </div>
                              <div class="product-info">
                                <a href="#" class="product-title text-dark">Abril Edwin</a>
                                <span class="product-description">
                                  MIS Staff  <span class="float-right" style="margin-top: -2px;">July 22, 1997</span>
                                </span>
                              </div>
                            </li>
                            <li class="item">
                              <div class="product-img">
                                <img class="border-radius" src="{{ asset('Employee/kyle.jpg') }}" alt="Product Image">
                              </div>
                              <div class="product-info">
                                <a href="#" class="product-title text-dark">Dalmacio Joshua Kyle</a>
                                <span class="product-description">
                                  MIS Staff  <span class="float-right" style="margin-top: -2px;">July 23, 1996</span>
                                </span>
                              </div>
                            </li>
                            <li class="item">
                              <div class="product-img">
                                <img class="border-radius" src="{{ asset('Employee/gmar.jpg') }}" alt="Product Image">
                              </div>
                              <div class="product-info">
                                <a href="#" class="product-title text-dark">Palma Gmar</a>
                                <span class="product-description">
                                  MIS Staff  <span class="float-right" style="margin-top: -2px;">July 24, 1995</span>
                                </span>
                              </div>
                            </li>
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
