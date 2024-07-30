@extends('layouts.master')

@section('body')
<style>
    .custom-gap .list-group-item {
        margin: 0px; /* Adjust the value to your preference */
        padding: 2px;
    }
    .profile-image-container {
        width: 100px !important; /* Adjust as needed */
        height: 100px !important; /* Adjust as needed */
        border-radius: 50% !important;
        overflow: hidden !important;
        display: inline-block !important;
        position: relative !;
    }

    .profile-image-container img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        border-radius: 50% !important;
    }

    .mtop {
        margin-top: -15px;
    }
    .bg-form{
        background-color:  #e9ecef;
    }
    .form-control:disabled, .form-control[readonly] {
        background-color: #ffffff;
        opacity: 1;
    }
    .form-control-sm {
        height: calc(1.5125rem + 2px);
        padding: .15rem .5rem;
        font-size: .750rem;
        line-height: 1.5;
        border-radius: .2rem;
        background-color: #ffffff !important;
    }
    .btn-sm{
        font-size: 10px !important;
        height: 25px !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }
    .select2-container--default.select2-container--disabled .select2-selection--single {
        background-color: #ffffff;
        cursor: default;
    }
</style>
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
                        @php
                            $hireDate = $employee->date_hired;
                            $currentDate = date('Y-m-d'); 

                            $startDate = new DateTime($hireDate);
                            $endDate = new DateTime($currentDate);

                            $interval = $startDate->diff($endDate);

                            $years = $interval->y;
                            $months = $interval->m;
                        @endphp
                        <li class="list-group-item">
                            <b>Employee ID. :</b> <span class="float-right text-muted">{{ $employee->emp_ID }}</span>
                        </li>
                        <li class="list-group-item">
                            <b>Item No. :</b> <span class="float-right text-muted">{{ $employee->item_no }}</span>
                        </li>
                        <li class="list-group-item">
                            <b>Service :</b> <span class="float-right text-muted">{{ $years.' years' .' '. $months. ' months' }}</span>
                        </li>
                    </ul>
                    @if($employee->stat_1 == 1)
                    <a href="#" class="btn btn-success btn-sm btn-block mt-2"><b>Active</b></a>
                    @else
                    <a href="#" class="btn btn-danger btn-sm btn-block mt-2"><b>Suspended</b></a>
                    @endif
                </div>
                <!-- /.card-body -->
            </div>

            <div class="card card-info">
                <div class="card-header" style="padding: 6px !important;">
                    <i class="fas fa-id-card"></i><b> PERSONAL DATA SHEET</b>
                </div>
                <div class="card-footer p-0">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a href="{{ route('empPDS') }}" class="nav-link">
                                <i class="text-dark pr-2 fas fa-user"></i> <span class="text-dark text-bold">Personal Information</span> 
                                <i class="float-right fas fa-check-circle text-success pt-1"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="text-muted pr-2 fas fa-users"></i>
                                <span class="text-muted text-bold">Family Background</span>
                                <i class="float-right fas fa-times-circle text-muted pt-1"></i>
                            </a>
                        </li>                        
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="text-muted pr-2 fas fa-graduation-cap"></i> <span class="text-muted text-bold">Educational Background</span>
                                <i class="float-right fas fa-times-circle text-muted pt-1"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="text-muted pr-2 fas fa-award"></i> <span class="text-muted text-bold">Eligibility</span>
                                <i class="float-right fas fa-times-circle text-muted pt-1"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="text-muted pr-2 fas fa-briefcase"></i> <span class="text-muted text-bold">Work Experience</span>
                                <i class="float-right fas fa-times-circle text-muted pt-1"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="text-muted pr-2 fas fa-hands-helping"></i> <span class="text-muted text-bold">Voluntary Work</span>
                                <i class="float-right fas fa-times-circle text-muted pt-1"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="text-muted pr-2 fas fa-chalkboard-teacher"></i> <span class="text-muted text-bold">Learning and Development</span>
                                <i class="float-right fas fa-times-circle text-muted pt-1"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="text-muted pr-2 fas fa-info-circle"></i> <span class="text-muted text-bold">Other Information</span>
                                <i class="float-right fas fa-times-circle text-muted pt-1"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="text-muted pr-2 fas fa-question-circle"></i> <span class="text-muted text-bold">Other Information Questions</span>
                                <i class="float-right fas fa-times-circle text-muted pt-1"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="text-muted pr-2 fas fa-address-book"></i> <span class="text-muted text-bold">References</span>
                                <i class="float-right fas fa-times-circle text-muted pt-1"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="text-muted pr-2 fas fa-id-card"></i> <span class="text-muted text-bold">Government Issued ID</span>
                                <i class="float-right fas fa-times-circle text-muted pt-1"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="text-muted pr-2 fas fa-coins"></i> <span class="text-muted text-bold">Income And Deductions</span>
                                <i class="float-right fas fa-times-circle text-muted pt-1"></i>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="text-muted pr-2 fas fa-eye"></i> <span class="text-muted text-bold">Preview Personal Data Sheet</span>
                                <i class="float-right fas fa-times-circle text-muted pt-1"></i>
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
                        <b>PERSONAL INFORMATION</b>
                    </h2>
                </div>
                <div class="card-body bg-form">
                    <form class="form-horizontal add-form" action="{{ route('empCreate') }}" method="POST">
                        @csrf
                        <div class="form-group mtop">
                            <div class="form-row">
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Date Hired</label>
                                    <input type="date" value="{{ $employee->date_hired }}" name="date_hired" data-column-id="{{ $empid }}" data-column-name="date_hired" name="date_hired" class="form-control form-control-sm update-field" id="date_hired">
                                </div>
                                @if($guard !== "employee")
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Item / Plantilla No.</label>
                                    <input type="text" value="{{ $employee->item_no }}" name="item_no" data-column-id="{{ $empid }}" data-column-name="item_no" name="item_no" class="form-control form-control-sm update-field" id="item_no" placeholder="N/A">
                                </div>
                                @endif
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Last Name</label><br>
                                    <input type="text" value="{{ ucfirst(strtolower($employee->lname)) }}" name="lname" data-column-id="{{ $empid }}" data-column-name="lname" class="form-control form-control-sm update-field" placeholder="N/A" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">First Name</label><br>
                                    <input type="text" value="{{ ucfirst(strtolower($employee->fname)) }}" name="fname" data-column-id="{{ $empid }}" data-column-name="fname" name="fname" class="form-control form-control-sm update-field" placeholder="N/A" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Middle Name</label><br>
                                    <input type="text" value="{{ ucfirst(strtolower($employee->mname)) }}" name="mname" data-column-id="{{ $empid }}" data-column-name="mname" name="mname" class="form-control form-control-sm update-field" placeholder="N/A" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Suffix</label><br>
                                    <select class="form-control form-control-sm update-field" name="suffix" required>
                                        <option disabled selected> Select </option>
                                        <option value="Jr." data-column-id="{{ $empid }}" data-column-name="suffix" @if($employee->suffix == "Jr.") selected @endif>Jr.</option>
                                        <option value="Sr." data-column-id="{{ $empid }}" data-column-name="suffix" @if($employee->suffix == "Sr.") selected @endif>Sr.</option>
                                        <option value="I" data-column-id="{{ $empid }}" data-column-name="suffix" @if($employee->suffix == "I") selected @endif>I</option>
                                        <option value="II" data-column-id="{{ $empid }}" data-column-name="suffix" @if($employee->suffix == "II") selected @endif>II</option>
                                        <option value="III" data-column-id="{{ $empid }}" data-column-name="suffix" @if($employee->suffix == "III") selected @endif>III</option>
                                        <option value="IV" data-column-id="{{ $empid }}" data-column-name="suffix" @if($employee->suffix == "IV") selected @endif>IV</option>
                                        <option value="V" data-column-id="{{ $empid }}" data-column-name="suffix" @if($employee->suffix == "V") selected @endif>V</option>
                                        <option value="" data-column-id="{{ $empid }}" data-column-name="suffix" @if($employee->suffix == "") selected @endif>N/A</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Prefix</label><br>
                                    <select class="form-control form-control-sm update-field" name="prefix" required>
                                        <option disabled selected> Select </option>
                                        <option value="Atty." data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "Atty.") selected @endif>Atty.</option>
                                        <option value="Dr." data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "Dr.") selected @endif>Dr.</option>
                                        <option value="Eng." data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "Eng.") selected @endif>Eng.</option>
                                        <option value="Mr." data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "Mr.") selected @endif>Mr.</option>
                                        <option value="Mrs." data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "Mrs.") selected @endif>Mrs.</option>
                                        <option value="Ms." data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "Ms.") selected @endif>Ms.</option>
                                        <option value="" data-column-id="{{ $empid }}" data-column-name="prefix" @if($employee->prefix == "") selected @endif>N/A</option>
                                    </select>
                                </div>                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Title Prefix</label><br>
                                    <input type="text" value="{{ ucfirst(strtolower($employee->title_prefix)) }}" name="title_prefix" data-column-id="{{ $empid }}" data-column-name="title_prefix" name="title_prefix" class="form-control form-control-sm update-field" placeholder="e.g MBA/ DPA / MD etc.">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Birth Date</label>
                                    <input type="date" value="{{ $employee->bdate }}" name="bdate" data-column-id="{{ $empid }}" data-column-name="bdate" name="bdate" class="form-control form-control-sm" id="bday" onchange="calculateAge()">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Age</label>
                                    <input type="text" value="{{ $employee->age }}" name="age" class="form-control form-control-sm" id="age" readonly>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Birth Place</label><br>
                                    <input type="text" value="{{ $employee->b_place }}" name="b_place" data-column-id="{{ $empid }}" data-column-name="b_place" name="b_place" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Sex</label><br>
                                    <select class="form-control form-control-sm update-field" name="sex" required>
                                        <option disabled selected> Select </option>
                                        <option value="Male" data-column-id="{{ $empid }}" data-column-name="sex" @if($employee->sex == "Male") selected @endif>Male</option>
                                        <option value="Female" data-column-id="{{ $empid }}" data-column-name="sex" @if($employee->sex == "Female") selected @endif>Female</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Civil Status</label><br>
                                    <select class="form-control form-control-sm update-field" name="civil_status">
                                        <option disabled selected> Select </option>
                                        <option value="Single" data-column-id="{{ $empid }}" data-column-name="civil_status" @if($employee->civil_status == "Single") selected @endif>Single</option>
                                        <option value="Married" data-column-id="{{ $empid }}" data-column-name="civil_status" @if($employee->civil_status == "Married") selected @endif>Married</option>
                                        <option value="Separated" data-column-id="{{ $empid }}" data-column-name="civil_status" @if($employee->civil_status == "Separated") selected @endif>Separated</option>
                                        <option value="Divorce" data-column-id="{{ $empid }}" data-column-name="civil_status" @if($employee->civil_status == "Divorce") selected @endif>Divorce</option>
                                        <option value="Widow" data-column-id="{{ $empid }}" data-column-name="civil_status" @if($employee->civil_status == "Widow") selected @endif>Widow</option>
                                        <option value="" data-column-id="{{ $empid }}" data-column-name="civil_status" @if($employee->civil_status == "") selected @endif>N/A</option>
                                    </select>
                                </div>                                

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Citizenship</label><br>
                                    <input type="text" value="{{ $employee->citizenship }}" name="citizenship" data-column-id="{{ $empid }}" data-column-name="citizenship" id="citizenship" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Employee Status</label><br>
                                    <select class="form-control form-control-sm select2 update-field" style="width: 100%;" name="emp_status" required>
                                        <option value=""> select </option>
                                        @foreach ($stat as $st)
                                            <option value="{{ $st->id }}" data-column-id="{{ $empid }}" data-column-name="emp_status" @if($employee->emp_status == $st->id) selected @endif>{{ $st->status_name }}</option>
                                        @endforeach
                                    </select>                                    
                                </div> 
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Position</label><br>
                                    <input type="text" value="{{ $employee->position }}" name="position" data-column-id="{{ $empid }}" data-column-name="position" id="position" name="position" id="position" class="form-control form-control-sm" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Campus</label><br>
                                    <select class="form-control form-control-sm select2 update-field" style="width: 100%;" name="camp_id" required>
                                        <option disabled selected> select </option>
                                        @foreach ($camp as $cp)
                                            <option value="{{ $cp->id }}" data-column-id="{{ $empid }}" data-column-name="camp_id" @if($employee->camp_id == $cp->id) selected @endif>{{ $cp->campus_name }}</option>
                                        @endforeach
                                    </select>
                                </div> 
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Department/Office</label><br>
                                    <select class="form-control form-control-sm select2 update-field" style="width: 100%;" name="emp_dept">
                                        <option value=""> select </option>
                                        @foreach ($offices as $of)
                                            <option  value="{{ $of->id }}" data-column-id="{{ $empid }}" data-column-name="emp_dept" @if($employee->emp_dept == $of->id) selected @endif>{{ $of->office_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Height (cm)</label><br>
                                    <input type="text" name="height_cm" id="height_cm" value="{{ $employee->height_cm }}" data-column-id="{{ $empid }}" data-column-name="height_cm" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                            
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Height (ft)</label><br>
                                    <input type="text" name="height_ft" id="height_ft" value="{{ $employee->height_ft }}" data-column-id="{{ $empid }}" data-column-name="height_ft" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                            
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Weight (kg)</label><br>
                                    <input type="text" name="weight_kg" id="weight_kg" value="{{ $employee->weight_kg }}" data-column-id="{{ $empid }}" data-column-name="weight_kg" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                            
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Weight (lb)</label><br>
                                    <input type="text" name="weight_lb" id="weight_lb" value="{{ $employee->weight_lb }}" data-column-id="{{ $empid }}" data-column-name="weight_lb" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Blood Type</label><br>
                                    <select class="form-control form-control-sm update-field" name="b_type">
                                        <option disabled selected> Select </option>
                                        <option value="A+" data-column-id="{{ $empid }}" data-column-name="b_type" @if($employee->b_type == "A+") selected @endif>A+</option>
                                        <option value="A-" data-column-id="{{ $empid }}" data-column-name="b_type" @if($employee->b_type == "A-") selected @endif>A-</option>
                                        <option value="AB+" data-column-id="{{ $empid }}" data-column-name="b_type" @if($employee->b_type == "AB+") selected @endif>AB+</option>
                                        <option value="AB-" data-column-id="{{ $empid }}" data-column-name="b_type" @if($employee->b_type == "AB-") selected @endif>AB-</option>
                                        <option value="B+" data-column-id="{{ $empid }}" data-column-name="b_type" @if($employee->b_type == "B+") selected @endif>B+</option>
                                        <option value="B-" data-column-id="{{ $empid }}" data-column-name="b_type" @if($employee->b_type == "B-") selected @endif>B-</option>
                                        <option value="O+" data-column-id="{{ $empid }}" data-column-name="b_type" @if($employee->b_type == "O+") selected @endif>O+</option>
                                        <option value="" data-column-id="{{ $empid }}" data-column-name="b_type" @if($employee->b_type == "") selected @endif>N/A</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">GSIS</label><br>
                                    <input type="text" name="gsis" id="gsis" value="{{ $employee->gsis }}" data-column-id="{{ $empid }}" data-column-name="gsis" class="form-control form-control-sm update-field"  placeholder="N/A">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">PAGIBIG</label><br>
                                    <input type="text" name="pagibig" id="pagibig" value="{{ $employee->pagibig }}" data-column-id="{{ $empid }}" data-column-name="pagibig" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">PHILHEALTH</label><br>
                                    <input type="text" name="philhealth" id="philhealth" value="{{ $employee->philhealth }}" data-column-id="{{ $empid }}" data-column-name="philhealth" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">SSS</label><br>
                                    <input type="text" name="sss" id="sss" value="{{ $employee->sss }}" data-column-id="{{ $empid }}" data-column-name="sss" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">TIN</label><br>
                                    <input type="text" name="tin" id="tin" value="{{ $employee->tin }}" data-column-id="{{ $empid }}" data-column-name="tin" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Telephone Number</label><br>
                                    <input type="text" name="telephone" id="telephone" value="{{ $employee->telephone }}" data-column-id="{{ $empid }}" data-column-name="telephone" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Email Address</label><br>
                                    <input type="email" name="org_email" id="org_email" value="{{ $employee->org_email }}" data-column-id="{{ $empid }}" data-column-name="org_email" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Mobile Number</label><br>
                                    <input type="text" name="mobile" id="mobile" value="{{ $employee->mobile }}" data-column-id="{{ $empid }}" data-column-name="mobile" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>

                            </div>
                        </div>
                        <div class="form-group mtop">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <h2 class="card-title text-success1 mt-3 mb-2">
                                        <b>RESIDENTIAL ADDRESS</b>
                                    </h2>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mtop">
                            <div class="form-row">
                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Region:</label>
                                    <select id="region" name="add_region" class="form-control select2 form-control-sm update-field" style="width: 100%;">
                                        <option value="">Select</option>
                                        @foreach($regions as $region)
                                            <option value="{{ $region->region_id }}" data-column-id="{{ $empid }}" data-column-name="add_region" @if($employee->add_region == $region->region_id) selected @endif>{{ $region->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Province:</label>
                                    <select id="province" name="add_prov" class="form-control select2 form-control-sm update-field" style="width: 100%;">
                                        <option disabled selected>Select</option>
                                        @foreach($hprovinces as $province)
                                            <option value="{{ $province->province_id }}" data-column-id="{{ $empid }}" data-column-name="add_prov" @if($employee->add_prov == $province->province_id) selected @endif>{{ $province->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary">City / Municipality:</label>
                                    <select id="city" name="add_city" class="form-control select2 form-control-sm update-field" style="width: 100%;">
                                        <option disabled selected>Select</option>
                                        @foreach($hcities as $city)
                                            <option value="{{ $city->city_id }}" data-column-id="{{ $empid }}" data-column-name="add_city" @if($employee->add_city == $city->city_id) selected @endif>{{ $city->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Barangay:</label>
                                    <select id="barangay" name="add_brgy" class="form-control select2 form-control-sm update-field" style="width: 100%;">
                                        <option disabled selected>Select</option>
                                        <option value="{{ (isset($employee->add_brgy)) ? $employee->add_brgy : '' }}" data-column-id="{{ $empid }}" data-column-name="add_brgy" selected>{{ (isset($employee->add_brgy)) ? $hbarangays->name : '' }}</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">House/Block/Lot No.</label><br>
                                    <input type="text" name="add_block" id="add_block" value="{{ $employee->add_block }}" data-column-id="{{ $empid }}" data-column-name="add_block" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Street</label><br>
                                    <input type="text" name="add_street" id="add_street" value="{{ $employee->add_street }}" data-column-id="{{ $empid }}" data-column-name="add_street" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Subdivision/Village</label><br>
                                    <input type="text" name="add_village" id="add_village" value="{{ $employee->add_village }}" data-column-id="{{ $empid }}" data-column-name="add_village" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">ZIP Code</label><br>
                                    <input type="number" name="add_zcode" id="add_zcode" value="{{ $employee->add_zcode }}" data-column-id="{{ $empid }}" data-column-name="add_zcode" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                            </div>
                        </div>
                        <div class="form-group mtop">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <h2 class="card-title text-success1 mt-3 mb-2">
                                        <b>PERMANENT ADDRESS</b>
                                    </h2>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mtop">
                            <div class="form-row">
                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Region:</label>
                                    <select id="region1" name="padd_region" class="form-control select2 form-control-sm update-field" style="width: 100%;">
                                        <option value="">Select</option>
                                        @foreach($regions as $region)
                                            <option value="{{ $region->region_id }}" data-column-id="{{ $empid }}" data-column-name="padd_region" @if($employee->padd_region == $region->region_id) selected @endif>{{ $region->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Province:</label>
                                    <select id="province1" name="padd_prov" class="form-control select2 form-control-sm update-field" style="width: 100%;">
                                        <option disabled selected>Select</option>
                                        @foreach($hprovinces as $province)
                                            <option value="{{ $province->province_id }}" data-column-id="{{ $empid }}" data-column-name="padd_prov" @if($employee->padd_prov == $province->province_id) selected @endif>{{ $province->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary">City / Municipality:</label>
                                    <select id="city1" name="padd_city" class="form-control select2 form-control-sm update-field" style="width: 100%;">
                                        <option disabled selected>Select</option>
                                        @foreach($hcities as $city)
                                            <option value="{{ $city->city_id }}" data-column-id="{{ $empid }}" data-column-name="padd_city" @if($employee->padd_city == $city->city_id) selected @endif>{{ $city->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Barangay:</label>
                                    <select id="barangay1" name="padd_brgy" class="form-control select2 form-control-sm update-field" style="width: 100%;">
                                        <option disabled selected>Select</option>
                                        <option value="{{ (isset($employee->padd_brgy)) ? $employee->padd_brgy : '' }}" data-column-id="{{ $empid }}" data-column-name="padd_brgy" selected>{{ (isset($employee->padd_brgy)) ? $hbarangays->name : '' }}</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">House/Block/Lot No.</label><br>
                                    <input type="text" name="padd_block" id="padd_block" value="{{ $employee->padd_block }}" data-column-id="{{ $empid }}" data-column-name="padd_block" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Street</label><br>
                                    <input type="text" name="padd_street" id="padd_street" value="{{ $employee->padd_street }}" data-column-id="{{ $empid }}" data-column-name="padd_street" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Subdivision/Village</label><br>
                                    <input type="text" name="padd_village" id="padd_village" value="{{ $employee->padd_village }}" data-column-id="{{ $empid }}" data-column-name="padd_village" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">ZIP Code</label><br>
                                    <input type="number" name="padd_zcode" id="padd_zcode" value="{{ $employee->padd_zcode }}" data-column-id="{{ $empid }}" data-column-name="padd_zcode" class="form-control form-control-sm update-field" placeholder="N/A">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-row float-right">
                                <div class="col-md-12">
                                    <button type="submit" name="btn-submit" class="btn btn-success">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                </div>
                            </div>
                        </div>   
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
@endsection