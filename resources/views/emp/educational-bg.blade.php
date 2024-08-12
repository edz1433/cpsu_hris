@extends('layouts.master')

@section('body')
@include('emp.style')
<section class="content">
<div class="container-fluid">
    <div class="row">
        @include('emp.submenu-side')
        <div class="col-lg-9">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h2 class="card-title text-success1">
                        <b>EDUCATIONAL BACKGROUND</b>
                    </h2>
                </div>
                <div class="card-body bg-form">
                    <div class="form-group mtop">
                        <div class="form-row">
                            <div class="col-md-12">
                                <h2 class="card-title text-success1 mt-3 mb-2">
                                    <b>ELEMENTARY</b>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mtop">
                        <div class="form-row lbel">
                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Name of School (Write in full)</label>
                                <input type="text" value="{{ $educBg->elem_school }}" name="elem_school" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="badge badge-secondary text-wrap lbel">Period of attendance</label>
                                <input type="text" value="{{ $educBg->elem_period }}" name="elem_period" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="ex: 2021-2024" oninput="validateDateRange(this)" onkeyup="restrictInput(this)">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="badge badge-secondary text-wrap lbel">Year Graduated</label>
                                <input type="month" value="{{ $educBg->elem_grad }}" name="elem_grad" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Scholarship / Academic Honors Received</label>
                                <input type="text" value="{{ $educBg->elem_honor }}" name="elem_honor" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mtop">
                        <div class="form-row">
                            <div class="col-md-12">
                                <h2 class="card-title text-success1 mt-3 mb-2">
                                    <b>SECONDARY</b>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mtop">
                        <div class="form-row lbel">
                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Name of School (Write in full)</label>
                                <input type="text" value="{{ $educBg->sec_school }}" name="sec_school" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="badge badge-secondary text-wrap lbel">Period of attendance</label>
                                <input type="text" value="{{ $educBg->sec_period }}" name="sec_period" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="ex: 2021 - 2024" oninput="validateDateRange(this)" onkeyup="restrictInput(this)">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="badge badge-secondary text-wrap lbel">Year Graduated</label>
                                <input type="month" value="{{ $educBg->sec_grad }}" name="sec_grad" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Scholarship / Academic Honors Received</label>
                                <input type="text" value="{{ $educBg->sec_honor }}" name="sec_honor" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mtop">
                        <div class="form-row">
                            <div class="col-md-12">
                                <h2 class="card-title text-success1 mt-3 mb-2">
                                    <b>VOCATIONAL / TRADE COURSE</b>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mtop">
                        <div class="form-row lbel">
                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Name of School (Write in full)</label>
                                <input type="text" value="{{ $educBg->voc_school }}" name="voc_school" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Basic Education/Degree/Course</label>
                                <input type="text" value="{{ $educBg->voc_course }}" name="voc_course" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Period of attendance</label>
                                <input type="text" value="{{ $educBg->voc_period }}" name="voc_period" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="ex: 2021 - 2024" oninput="validateDateRange(this)" onkeyup="restrictInput(this)">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Highest Level / Units Earned (if not graduated)</label>
                                <input type="text" value="{{ $educBg->voc_level }}" name="voc_level" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Year Graduated</label>
                                <input type="month" value="{{ $educBg->voc_grad }}" name="voc_grad" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Scholarship / Academic Honors Received</label>
                                <input type="text" value="{{ $educBg->voc_honor }}" name="voc_honor" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mtop">
                        <div class="form-row">
                            <div class="col-md-12">
                                <h2 class="card-title text-success1 mt-3 mb-2">
                                    <b>COLLEGE</b>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mtop">
                        <div class="form-row lbel">
                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Name of School (Write in full)</label>
                                <input type="text" value="{{ $educBg->coll_school }}" name="coll_school" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Basic Education/Degree/Course</label>
                                <input type="text" value="{{ $educBg->coll_course }}" name="coll_course" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Period of attendance</label>
                                <input type="text" value="{{ $educBg->coll_period }}" name="coll_period" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="ex: 2021 - 2024" oninput="validateDateRange(this)" onkeyup="restrictInput(this)">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Highest Level / Units Earned (if not graduated)</label>
                                <input type="text" value="{{ $educBg->coll_level }}" name="coll_level" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Year Graduated</label>
                                <input type="month" value="{{ $educBg->coll_grad }}" name="coll_grad" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Scholarship / Academic Honors Received</label>
                                <input type="text" value="{{ $educBg->coll_honor }}" name="coll_honor" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mtop">
                        <div class="form-row">
                            <div class="col-md-12">
                                <h2 class="card-title text-success1 mt-3 mb-2">
                                    <b>GRADUATE STUDIES</b>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mtop">
                        <div class="form-row lbel">
                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Name of School (Write in full)</label>
                                <input type="text" value="{{ $educBg->grad_school }}" name="grad_school" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Basic Education/Degree/Course</label>
                                <input type="text" value="{{ $educBg->grad_course }}" name="grad_course" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Period of attendance</label>
                                <input type="text" value="{{ $educBg->grad_period }}" name="grad_period" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="ex: 2021 - 2024" oninput="validateDateRange(this)" onkeyup="restrictInput(this)">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Highest Level / Units Earned (if not graduated)</label>
                                <input type="text" value="{{ $educBg->grad_level }}" name="grad_level" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Year Graduated</label>
                                <input type="month" value="{{ $educBg->grad_grad }}" name="grad_grad" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>

                            <div class="col-md-4">
                                <label class="badge badge-secondary text-wrap lbel">Scholarship / Academic Honors Received</label>
                                <input type="text" value="{{ $educBg->grad_honor }}" name="grad_honor" data-column-id="{{ $empid }}" class="form-control form-control-sm update-field" placeholder="N/A">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
<script>
    function validateDateRange(input) {
        const value = input.value;
        const regex = /^\d{4}-\d{4}$/;
        if (regex.test(value)) {
            const [startYear, endYear] = value.split('-').map(Number);
            if (startYear < 1900 || endYear > 2099 || startYear > endYear) {
                input.setCustomValidity('Please enter a valid year range (YYYY-YYYY).');
                input.reportValidity();
            } else {
                input.setCustomValidity('');
            }
        } else {
            input.setCustomValidity('Please enter the date range in YYYY-YYYY format.');
            input.reportValidity();
        }
    }
    
    function restrictInput(input) {
        input.value = input.value.replace(/[^0-9-]/g, '');
    }
    </script>
@endsection