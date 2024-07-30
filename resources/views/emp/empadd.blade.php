@extends('layouts.master')

@section('body')
<style>
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
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
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
                                <div class="col-md-1">
                                    <label class="badge badge-secondary lbel">Profile</label><br>
                                    <button type="button" id="capture-toggle" class="btn btn-secondary btn-block btn-sm" data-toggle="modal" data-target="#camera-modal"><i class="fas fa-camera"></i> </button>
                                
                                    <input type="hidden" name="ProfileImage" id="captured-image" required>
                                </div>
                                <div class="col-md-1">
                                    <label style="margin-top: 14px;" class="lbel"></label><br>
                                    <input type="file" id="profile-image-input" name="ProfileImage1" style="display: none;"  onchange="handleImageUpload()">
                                    <button type="button" id="capture-toggle1" class="btn btn-secondary btn-block btn-sm" onclick="document.getElementById('profile-image-input').click();">
                                        <i class="fas fa-upload"></i> 
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Date Hired</label>
                                    <input type="date" name="date_hired" class="form-control form-control-sm" id="date_hired">
                                </div>
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Item / Plantilla No.</label>
                                    <input type="text" name="item_no" class="form-control form-control-sm" id="item_no" placeholder="N/A">
                                </div>
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Last Name</label><br>
                                    <input type="text" name="lname" class="form-control form-control-sm" placeholder="N/A" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">First Name</label><br>
                                    <input type="text" name="fname" class="form-control form-control-sm" placeholder="N/A" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Middle Name</label><br>
                                    <input type="text" name="mname" class="form-control form-control-sm" placeholder="N/A" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Suffix</label><br>
                                    <select class="form-control form-control-sm" name="suffix">
                                        <option disabled selected> Select </option>
                                        <option value="Jr.">Jr.</option>
                                        <option value="Sr.">Sr.</option>
                                        <option value="I">I</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="IV">IV</option>
                                        <option value="V">V</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Prefix</label><br>
                                    <select class="form-control form-control-sm" name="prefix">
                                        <option disabled selected> Select </option>
                                        <option>Atty.</option>
                                        <option>Dr.</option>
                                        <option>Eng.</option>
                                        <option>Mr.</option>
                                        <option>Mrs.</option>
                                        <option>Ms.</option>
                                        <option>Not Applicable</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Title Prefix</label><br>
                                    <input type="text" name="title_prefix" class="form-control form-control-sm" placeholder="e.g MBA/ DPA / MD etc.">
                                </div>
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Birth Date</label>
                                    <input type="date" name="bdate" class="form-control form-control-sm" id="bday" onchange="calculateAge()">
                                </div>

                                <div class="col-md-1">
                                    <label class="badge badge-secondary lbel">Age</label>
                                    <input type="text" name="age" class="form-control form-control-sm" id="age" readonly>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Birth Place</label><br>
                                    <input type="text" name="b_place" class="form-control form-control-sm" placeholder="N/A">
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Sex</label><br>
                                    <select class="form-control form-control-sm" name="sex" required>
                                        <option disabled selected> Select </option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Civil Status</label><br>
                                    <select class="form-control form-control-sm" name="civil_status">
                                        <option disabled selected> Select </option>
                                        <option>Single</option>
                                        <option>Married</option>
                                        <option>Separated</option>
                                        <option>Divorce</option>
                                        <option>Widow</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Citizenship</label><br>
                                    <input type="text" name="citizenship" id="citizenship" class="form-control form-control-sm" placeholder="N/A">
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Employe Status</label><br>
                                    <select class="form-control form-control-sm select2" style="width: 100%;" name="emp_status" required>
                                        <option value=""> select </option>
                                        @foreach ($stat as $st)
                                            <option value="{{ $st->id }}">{{ $st->status_name }}</option>
                                        @endforeach
                                    </select>
                                </div> 
                                
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Position</label><br>
                                    <input type="text" name="position" id="position" class="form-control form-control-sm" placeholder="N/A">
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Campus</label><br>
                                    <select class="form-control form-control-sm select2" style="width: 100%;" name="camp_id" required>
                                        <option value=""> select </option>
                                        @foreach ($camp as $cp)
                                            <option value="{{ $cp->id }}">{{ $cp->campus_name }}</option>
                                        @endforeach
                                    </select>
                                </div> 
                                
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Department/Office</label><br>
                                    <select class="form-control form-control-sm select2" style="width: 100%;" name="emp_dept">
                                        <option value=""> select </option>
                                        @foreach ($offices as $q)
                                            <option value="{{ $q->id }}">{{ $q->office_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Height (cm)</label><br>
                                    <input type="text" name="height_cm" id="height_cm" class="form-control form-control-sm" placeholder="N/A">
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Height (ft)</label><br>
                                    <input type="text" name="height_ft" id="height_ft" class="form-control form-control-sm" placeholder="N/A">
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Weight (kg)</label><br>
                                    <input type="text" name="weight_kg" id="weight_kg" class="form-control form-control-sm" placeholder="N/A">
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Weight (lb)</label><br>
                                    <input type="text" name="weight_lb" id="weight_lb" class="form-control form-control-sm" placeholder="N/A">
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">Blood Type</label><br>
                                    <select class="form-control form-control-sm" name="b_type">
                                        <option disabled selected> Select </option>
                                        <option>A+</option>
                                        <option>A-</option>
                                        <option>AB+</option>
                                        <option>AB-</option>
                                        <option>B+</option>
                                        <option>B-</option>
                                        <option>O+</option>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">GSIS</label><br>
                                    <input type="text" name="gsis" class="form-control form-control-sm"  placeholder="N/A">
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">PAGIBIG</label><br>
                                    <input type="text" name="pagibig" class="form-control form-control-sm" placeholder="N/A">
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">PHILHEALTH</label><br>
                                    <input type="text" name="philhealth" class="form-control form-control-sm" placeholder="N/A">
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">SSS</label><br>
                                    <input type="text" name="sss" class="form-control form-control-sm" placeholder="N/A">
                                </div>

                                <div class="col-md-2">
                                    <label class="badge badge-secondary lbel">TIN</label><br>
                                    <input type="text" name="tin" class="form-control form-control-sm" placeholder="N/A">
                                </div>

                                <div class="col-md-4">
                                    <label class="badge badge-secondary lbel">Telephone Number</label><br>
                                    <input type="text" name="telephone" class="form-control form-control-sm" placeholder="N/A">
                                </div>

                                <div class="col-md-4">
                                    <label class="badge badge-secondary lbel">Email Address</label><br>
                                    <input type="text" name="org_email" class="form-control form-control-sm" placeholder="N/A">
                                </div>

                                <div class="col-md-4">
                                    <label class="badge badge-secondary lbel">Mobile Number</label><br>
                                    <input type="text" name="mobile" class="form-control form-control-sm" placeholder="N/A">
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
                                    <select id="region" name="add_region" class="form-control select2 form-control-sm" style="width: 100%;">
                                        <option value="">Select</option>
                                        @foreach($regions as $region)
                                            <option value="{{ $region->region_id }}">{{ $region->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Province:</label>
                                    <select id="province" name="add_prov" class="form-control select2 form-control-sm" style="width: 100%;" disabled>
                                        <option value="">Select</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary">City / Municipality:</label>
                                    <select id="city" name="add_city" class="form-control select2 form-control-sm" style="width: 100%;" disabled>
                                        <option value="">Select</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Barangay:</label>
                                    <select id="barangay" name="add_brgy" class="form-control select2 form-control-sm" style="width: 100%;" disabled>
                                        <option value="">Select</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">House/Block/Lot No.</label><br>
                                    <input type="text" name="add_block" class="form-control form-control-sm" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Street</label><br>
                                    <input type="text" name="add_street" class="form-control form-control-sm" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Subdivision/Village</label><br>
                                    <input type="text" name="add_village" class="form-control form-control-sm" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">ZIP Code</label><br>
                                    <input type="number" name="add_zcode" class="form-control form-control-sm" placeholder="N/A">
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
                                    <select id="region1" name="padd_region" class="form-control select2 form-control-sm" style="width: 100%;">
                                        <option value="">Select</option>
                                        @foreach($regions as $region)
                                            <option value="{{ $region->region_id }}">{{ $region->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Province:</label>
                                    <select id="province1" name="padd_prov" class="form-control select2 form-control-sm" style="width: 100%;" disabled>
                                        <option value="">Select</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary">City / Municipality:</label>
                                    <select id="city1" name="padd_city" class="form-control select2 form-control-sm" style="width: 100%;" disabled>
                                        <option value="">Select</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="badge badge-secondary">Barangay:</label>
                                    <select id="barangay1" name="padd_brgy" class="form-control select2 form-control-sm" style="width: 100%;" disabled>
                                        <option value="">Select</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">House/Block/Lot No.</label><br>
                                    <input type="text" name="padd_block" class="form-control form-control-sm" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Street</label><br>
                                    <input type="text" name="padd_street" class="form-control form-control-sm" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">Subdivision/Village</label><br>
                                    <input type="text" name="padd_village" class="form-control form-control-sm" placeholder="N/A">
                                </div>
                                <div class="col-md-3">
                                    <label class="badge badge-secondary lbel">ZIP Code</label><br>
                                    <input type="number" name="padd_zcode" class="form-control form-control-sm" placeholder="N/A">
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

@include('emp.modal-camera')

@endsection