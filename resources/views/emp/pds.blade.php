@extends('layouts.master')

@section('body')
@php
    // Options whose <option> tags carry data-column-*: the auto-save script reads the column from the selected option.
    $suffixes = ['' => 'N/A', 'Jr.' => 'Jr.', 'Sr.' => 'Sr.', 'I' => 'I', 'II' => 'II', 'III' => 'III', 'IV' => 'IV', 'V' => 'V'];
    $prefixes = ['' => 'N/A', 'Ph.D.' => 'Ph.D.', 'Atty.' => 'Atty.', 'Dr.' => 'Dr.', 'Engr.' => 'Engr.', 'RChE.' => 'RChE.', 'J.D.' => 'J.D.', 'M.S.W.' => 'M.S.W.', 'C.P.A.' => 'C.P.A.', 'C.L.E.A.' => 'C.L.E.A.', 'DIT.' => 'DIT.'];
    $titlePrefixes = ['MBA', 'DPA', 'MPA', 'MD', 'RN', 'LLM', 'MSW', 'CPA', 'DIT', 'CNA', 'CHRP'];
    $sexes = ['Male', 'Female'];
    $civilStatuses = ['Single' => 'Single', 'Married' => 'Married', 'Separated' => 'Separated', 'Widowed' => 'Widowed', 'Other' => 'Other/s'];
    $bloodTypes = ['A+' => 'A+', 'A-' => 'A-', 'AB+' => 'AB+', 'AB-' => 'AB-', 'B+' => 'B+', 'B-' => 'B-', 'O+' => 'O+', '' => 'N/A'];
    $countries = [
        'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola', 'Antigua and Barbuda', 'Argentina', 'Armenia', 'Australia', 'Austria', 'Azerbaijan',
        'Bahamas', 'Bahrain', 'Bangladesh', 'Barbados', 'Belarus', 'Belgium', 'Belize', 'Benin', 'Bhutan', 'Bolivia', 'Bosnia and Herzegovina', 'Botswana',
        'Brazil', 'Brunei', 'Bulgaria', 'Burkina Faso', 'Burundi', 'Cabo Verde', 'Cambodia', 'Cameroon', 'Canada', 'Central African Republic', 'Chad', 'Chile',
        'China', 'Colombia', 'Comoros', 'Congo', 'Costa Rica', 'Croatia', 'Cuba', 'Cyprus', 'Czech Republic', 'Democratic Republic of the Congo', 'Denmark',
        'Djibouti', 'Dominica', 'Dominican Republic', 'Ecuador', 'Egypt', 'El Salvador', 'Equatorial Guinea', 'Eritrea', 'Estonia', 'Eswatini', 'Ethiopia',
        'Fiji', 'Finland', 'France', 'Gabon', 'Gambia', 'Georgia', 'Germany', 'Ghana', 'Greece', 'Grenada', 'Guatemala', 'Guinea', 'Guinea-Bissau', 'Guyana',
        'Haiti', 'Honduras', 'Hungary', 'Iceland', 'India', 'Indonesia', 'Iran', 'Iraq', 'Ireland', 'Israel', 'Italy', 'Ivory Coast', 'Jamaica', 'Japan',
        'Jordan', 'Kazakhstan', 'Kenya', 'Kiribati', 'Kuwait', 'Kyrgyzstan', 'Laos', 'Latvia', 'Lebanon', 'Lesotho', 'Liberia', 'Libya', 'Liechtenstein',
        'Lithuania', 'Luxembourg', 'Madagascar', 'Malawi', 'Malaysia', 'Maldives', 'Mali', 'Malta', 'Marshall Islands', 'Mauritania', 'Mauritius', 'Mexico',
        'Micronesia', 'Moldova', 'Monaco', 'Mongolia', 'Montenegro', 'Morocco', 'Mozambique', 'Myanmar', 'Namibia', 'Nauru', 'Nepal', 'Netherlands',
        'New Zealand', 'Nicaragua', 'Niger', 'Nigeria', 'North Korea', 'North Macedonia', 'Norway', 'Oman', 'Pakistan', 'Palau', 'Panama', 'Papua New Guinea',
        'Paraguay', 'Peru', 'Philippines', 'Poland', 'Portugal', 'Qatar', 'Romania', 'Russia', 'Rwanda', 'Saint Kitts and Nevis', 'Saint Lucia',
        'Saint Vincent and the Grenadines', 'Samoa', 'San Marino', 'Sao Tome and Principe', 'Saudi Arabia', 'Senegal', 'Serbia', 'Seychelles', 'Sierra Leone',
        'Singapore', 'Slovakia', 'Slovenia', 'Solomon Islands', 'Somalia', 'South Africa', 'South Korea', 'South Sudan', 'Spain', 'Sri Lanka', 'Sudan',
        'Suriname', 'Sweden', 'Switzerland', 'Syria', 'Taiwan', 'Tajikistan', 'Tanzania', 'Thailand', 'Timor-Leste', 'Togo', 'Tonga', 'Trinidad and Tobago',
        'Tunisia', 'Turkey', 'Turkmenistan', 'Tuvalu', 'Uganda', 'Ukraine', 'United Arab Emirates', 'United Kingdom', 'United States', 'Uruguay',
        'Uzbekistan', 'Vanuatu', 'Vatican City', 'Venezuela', 'Vietnam', 'Yemen', 'Zambia', 'Zimbabwe',
    ];
    $col = fn ($column) => 'data-column-id="' . e($empid) . '" data-column-name="' . e($column) . '"';
    $isAdmin = $guard == "web";
@endphp
<div class="container-fluid dash">
    @include('emp.partials.pds-head', ['pdsTitle' => 'Personal Information', 'saveUrls' => [route('employeeUpdate')]])

    <div class="row">
        @include('emp.submenu-side')
        <div class="col-lg-9">
            {{-- Nothing is submitted from here: every field saves on change, so Enter must not post this form. --}}
            <form class="dtr-form pds-form add-form" action="{{ route('empCreate') }}" method="POST" onsubmit="return false;">
                @csrf

                <div class="dash-card">
                    <div class="dash-card-header">
                        <h5><i class="fas fa-briefcase" style="color: var(--cpsu-green-600);"></i>Employment</h5>
                    </div>
                    <div class="dash-card-body">
                        <div class="form-row">
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="date_hired">Date hired</label>
                                <input type="date" value="{{ $employee->date_hired }}" name="date_hired" {!! $col('date_hired') !!} class="form-control update-field" id="date_hired">
                            </div>
                            @if($isAdmin)
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="item_no">Item / Plantilla No.</label>
                                <input type="text" value="{{ $employee->item_no }}" name="item_no" {!! $col('item_no') !!} class="form-control update-field" id="item_no" placeholder="N/A">
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="pds_emp_status">Employee status</label>
                                <select class="form-control select2 update-field" style="width: 100%;" name="emp_status" id="pds_emp_status" required>
                                    <option value=""> select </option>
                                    @foreach ($stat as $st)
                                        <option value="{{ $st->id }}" {!! $col('emp_status') !!} @if($employee->emp_status == $st->id) selected @endif>{{ $st->status_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="col-md-{{ $isAdmin ? 6 : 8 }} dtr-field">
                                <label class="dtr-label" for="position">Position</label>
                                <input type="text" value="{{ $employee->position }}" name="position" {!! $col('position') !!} id="position" class="form-control update-field" placeholder="N/A">
                            </div>
                            <div class="col-md-6 dtr-field">
                                <label class="dtr-label" for="pds_camp_id">Campus</label>
                                <select class="form-control select2 update-field" style="width: 100%;" name="camp_id" id="pds_camp_id" required>
                                    <option disabled selected> select </option>
                                    @foreach ($camp as $cp)
                                        <option value="{{ $cp->id }}" {!! $col('camp_id') !!} @if($employee->camp_id == $cp->id) selected @endif>{{ $cp->campus_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 dtr-field">
                                <label class="dtr-label" for="pds_emp_dept">Department / Office</label>
                                <select class="form-control select2 update-field" style="width: 100%;" name="emp_dept" id="pds_emp_dept">
                                    <option value=""> select </option>
                                    @foreach ($offices as $of)
                                        <option value="{{ $of->id }}" {!! $col('emp_dept') !!} @if($employee->emp_dept == $of->id) selected @endif>{{ $of->office_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 dtr-field">
                                <label class="dtr-label" for="pds_supervisor">Immediate supervisor</label>
                                <select class="form-control select2 update-field" style="width: 100%;" name="supervisor" id="pds_supervisor">
                                    <option value="0" {!! $col('supervisor') !!}> select </option>
                                    @foreach ($supervisor as $sup)
                                        <option value="{{ $sup->id }}" {!! $col('supervisor') !!} @if($employee->supervisor == $sup->id) selected @endif>{{ strtoupper($sup->lname) }} {{ strtoupper($sup->fname) }} {{ strtoupper($sup->mname) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dash-card">
                    <div class="dash-card-header">
                        <h5><i class="fas fa-user" style="color: var(--cpsu-green-600);"></i>Personal details</h5>
                    </div>
                    <div class="dash-card-body">
                        <div class="form-row">
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="pds_lname">Last name</label>
                                <input type="text" value="{{ $employee->lname }}" name="lname" id="pds_lname" {!! $col('lname') !!} class="form-control update-field" placeholder="N/A" required>
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="pds_fname">First name</label>
                                <input type="text" value="{{ $employee->fname }}" name="fname" id="pds_fname" {!! $col('fname') !!} class="form-control update-field" placeholder="N/A" required>
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="pds_mname">Middle name</label>
                                <input type="text" value="{{ $employee->mname }}" name="mname" id="pds_mname" {!! $col('mname') !!} class="form-control update-field" placeholder="N/A" required>
                            </div>
                            <div class="col-md-4 col-6 dtr-field">
                                <label class="dtr-label" for="pds_suffix">Suffix</label>
                                <select class="form-control update-field" name="suffix" id="pds_suffix" required>
                                    @foreach($suffixes as $value => $label)
                                        <option value="{{ $value }}" {!! $col('suffix') !!} @if($employee->suffix == $value) selected @endif>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-6 dtr-field">
                                <label class="dtr-label" for="pds_prefix">Prefix</label>
                                <select class="form-control update-field" name="prefix" id="pds_prefix" required>
                                    @foreach($prefixes as $value => $label)
                                        <option value="{{ $value }}" {!! $col('prefix') !!} @if($employee->prefix == $value) selected @endif>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="pds_title_prefix">Title prefix</label>
                                <select class="form-control update-field" name="title_prefix" id="pds_title_prefix">
                                    <option value="" {!! $col('title_prefix') !!} @if($employee->title_prefix == "") selected @endif>N/A</option>
                                    @foreach($titlePrefixes as $title)
                                        <option value="{{ $title }}" {!! $col('title_prefix') !!} @if($employee->title_prefix == $title) selected @endif>{{ $title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 col-8 dtr-field">
                                <label class="dtr-label" for="bday">Birth date</label>
                                <input type="date" value="{{ $employee->bdate }}" name="bdate" {!! $col('bdate') !!} class="form-control update-field" id="bday" onchange="calculateAge()">
                            </div>
                            <div class="col-md-2 col-4 dtr-field">
                                <label class="dtr-label" for="age">Age</label>
                                <input type="text" value="{{ $employee->bdate ? \Carbon\Carbon::parse($employee->bdate)->diffInYears(now()) : '' }}" name="age" class="form-control" id="age" readonly tabindex="-1">
                            </div>
                            <div class="col-md-6 dtr-field">
                                <label class="dtr-label" for="pds_b_place">Birth place</label>
                                <input type="text" value="{{ $employee->b_place }}" name="b_place" id="pds_b_place" {!! $col('b_place') !!} class="form-control update-field" placeholder="N/A">
                            </div>

                            <div class="col-md-4 col-6 dtr-field">
                                <label class="dtr-label" for="pds_sex">Sex</label>
                                <select class="form-control update-field" name="sex" id="pds_sex" required>
                                    <option disabled selected> Select </option>
                                    @foreach($sexes as $sex)
                                        <option value="{{ $sex }}" {!! $col('sex') !!} @if($employee->sex == $sex) selected @endif>{{ $sex }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-6 dtr-field">
                                <label class="dtr-label" for="pds_civil_status">Civil status</label>
                                <select class="form-control update-field" name="civil_status" id="pds_civil_status">
                                    <option disabled selected> Select </option>
                                    @foreach($civilStatuses as $value => $label)
                                        <option value="{{ $value }}" {!! $col('civil_status') !!} @if($employee->civil_status == $value) selected @endif>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="pds_b_type">Blood type</label>
                                <select class="form-control update-field" name="b_type" id="pds_b_type">
                                    <option disabled selected> Select </option>
                                    @foreach($bloodTypes as $value => $label)
                                        <option value="{{ $value }}" {!! $col('b_type') !!} @if($employee->b_type == $value) selected @endif>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="pds-subhead">Citizenship</div>
                        <div class="form-row">
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="pds_citizenship">Citizenship</label>
                                <select class="form-control update-field" name="citizenship" id="pds_citizenship">
                                    <option disabled selected> Select </option>
                                    <option value="1" {!! $col('citizenship') !!} @if($employee->citizenship == 1) selected @endif>Filipino</option>
                                    <option value="2" {!! $col('citizenship') !!} @if($employee->citizenship == 2) selected @endif>Dual Citizenship</option>
                                </select>
                            </div>
                            <div class="col-md-4 dtr-field">
                                <span class="dtr-label">Dual citizenship by</span>
                                <div class="pds-radios">
                                    <label class="pds-radio" for="by-birth">
                                        <input class="c-radio update-field" value="1" type="radio" name="c_category" {!! $col('c_category') !!} id="by-birth" @if($employee->c_category == 1) checked @endif>
                                        <span>Birth</span>
                                    </label>
                                    <label class="pds-radio" for="by-naturalization">
                                        <input class="c-radio update-field" value="2" type="radio" name="c_category" {!! $col('c_category') !!} id="by-naturalization" @if($employee->c_category == 2) checked @endif>
                                        <span>Naturalization</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="pds_country">Country</label>
                                <select class="form-control update-field" name="country" id="pds_country">
                                    <option value="" disabled selected>Select</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country }}" {!! $col('country') !!} @if($employee->country == $country) selected @endif>{{ $country }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <p class="pds-hint mb-0">"Dual citizenship by" and Country open up once Citizenship is set to Dual Citizenship.</p>
                    </div>
                </div>

                <div class="dash-card">
                    <div class="dash-card-header">
                        <h5><i class="fas fa-ruler-vertical" style="color: var(--cpsu-green-600);"></i>Height and weight</h5>
                        <span class="dash-card-hint d-none d-sm-inline">Fill in either unit; the other converts on its own</span>
                    </div>
                    <div class="dash-card-body">
                        <div class="form-row">
                            <div class="col-md-3 col-6 dtr-field">
                                <label class="dtr-label" for="height_cm">Height (cm)</label>
                                <input type="text" inputmode="decimal" name="height_cm" id="height_cm" value="{{ $employee->height_cm }}" {!! $col('height_cm') !!} class="form-control update-field" placeholder="N/A">
                            </div>
                            <div class="col-md-3 col-6 dtr-field">
                                <label class="dtr-label" for="height_m">Height (m)</label>
                                <input type="text" inputmode="decimal" name="height_m" id="height_m" value="{{ $employee->height_m }}" {!! $col('height_m') !!} class="form-control update-field" placeholder="N/A">
                            </div>
                            <div class="col-md-3 col-6 dtr-field">
                                <label class="dtr-label" for="weight_kg">Weight (kg)</label>
                                <input type="text" inputmode="decimal" name="weight_kg" id="weight_kg" value="{{ $employee->weight_kg }}" {!! $col('weight_kg') !!} class="form-control update-field" placeholder="N/A">
                            </div>
                            <div class="col-md-3 col-6 dtr-field">
                                <label class="dtr-label" for="weight_lb">Weight (lb)</label>
                                <input type="text" inputmode="decimal" name="weight_lb" id="weight_lb" value="{{ $employee->weight_lb }}" {!! $col('weight_lb') !!} class="form-control update-field" placeholder="N/A">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dash-card">
                    <div class="dash-card-header">
                        <h5><i class="fas fa-address-card" style="color: var(--cpsu-green-600);"></i>Government IDs and contact</h5>
                    </div>
                    <div class="dash-card-body">
                        <div class="form-row">
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="gsis">GSIS ID no.</label>
                                <input type="text" name="gsis" id="gsis" value="{{ $employee->gsis }}" {!! $col('gsis') !!} class="form-control update-field" placeholder="N/A">
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="pagibig">PAG-IBIG ID no.</label>
                                <input type="text" name="pagibig" id="pagibig" value="{{ $employee->pagibig }}" {!! $col('pagibig') !!} class="form-control update-field" placeholder="N/A">
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="philhealth">PhilHealth no.</label>
                                <input type="text" name="philhealth" id="philhealth" value="{{ $employee->philhealth }}" {!! $col('philhealth') !!} class="form-control update-field" placeholder="N/A">
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="sss">UMID ID no.</label>
                                <input type="text" name="sss" id="sss" value="{{ $employee->sss }}" {!! $col('sss') !!} class="form-control update-field" placeholder="N/A">
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="tin">TIN</label>
                                <input type="text" name="tin" id="tin" value="{{ $employee->tin }}" {!! $col('tin') !!} class="form-control update-field" placeholder="N/A">
                            </div>
                        </div>

                        <div class="pds-subhead">Contact</div>
                        <div class="form-row">
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="mobile">Mobile number</label>
                                <input type="text" inputmode="numeric" name="mobile" id="mobile" value="{{ $employee->mobile }}" {!! $col('mobile') !!} class="form-control update-field" placeholder="09XX-XXX-XXXX">
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="telephone">Telephone number</label>
                                <input type="text" name="telephone" id="telephone" value="{{ $employee->telephone }}" {!! $col('telephone') !!} class="form-control update-field" placeholder="N/A">
                            </div>
                            <div class="col-md-4 dtr-field">
                                <label class="dtr-label" for="org_email">Email address @if(!$isAdmin)<span class="font-weight-normal">(set by HR)</span>@endif</label>
                                <input type="email" name="org_email" id="org_email" value="{{ $employee->org_email }}" {!! $col('org_email') !!} class="form-control {{ $isAdmin ? 'update-field' : '' }}" placeholder="N/A" @if(!$isAdmin) readonly @endif>
                            </div>
                        </div>
                    </div>
                </div>

                @foreach([
                    ['Residential address', 'fa-home', '', ['region' => $regions, 'province' => $hprovinces, 'city' => $hcities, 'barangay' => $hbarangays], 'add_'],
                    ['Permanent address', 'fa-map-marker-alt', '1', ['region' => $regions, 'province' => $gprovinces, 'city' => $gcities, 'barangay' => $gbarangays], 'padd_'],
                ] as [$addrTitle, $addrIcon, $idSuffix, $places, $p])
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h5><i class="fas {{ $addrIcon }}" style="color: var(--cpsu-green-600);"></i>{{ $addrTitle }}</h5>
                    </div>
                    <div class="dash-card-body">
                        <div class="form-row">
                            <div class="col-md-6 col-xl-3 dtr-field">
                                <label class="dtr-label" for="region{{ $idSuffix }}">Region</label>
                                <select id="region{{ $idSuffix }}" name="{{ $p }}region" class="form-control select2 update-field" style="width: 100%;">
                                    <option value="">Select</option>
                                    @foreach($places['region'] as $region)
                                        <option value="{{ $region->region_id }}" {!! $col($p . 'region') !!} @if($employee->{$p . 'region'} == $region->region_id) selected @endif>{{ $region->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-xl-3 dtr-field">
                                <label class="dtr-label" for="province{{ $idSuffix }}">Province</label>
                                <select id="province{{ $idSuffix }}" name="{{ $p }}prov" class="form-control select2 update-field" style="width: 100%;">
                                    <option disabled selected>Select</option>
                                    @foreach($places['province'] as $province)
                                        <option value="{{ $province->province_id }}" {!! $col($p . 'prov') !!} @if($employee->{$p . 'prov'} == $province->province_id) selected @endif>{{ $province->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-xl-3 dtr-field">
                                <label class="dtr-label" for="city{{ $idSuffix }}">City / Municipality</label>
                                <select id="city{{ $idSuffix }}" name="{{ $p }}city" class="form-control select2 update-field" style="width: 100%;">
                                    <option disabled selected>Select</option>
                                    @foreach($places['city'] as $city)
                                        <option value="{{ $city->city_id }}" {!! $col($p . 'city') !!} @if($employee->{$p . 'city'} == $city->city_id) selected @endif>{{ $city->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-xl-3 dtr-field">
                                <label class="dtr-label" for="barangay{{ $idSuffix }}">Barangay</label>
                                <select id="barangay{{ $idSuffix }}" name="{{ $p }}brgy" class="form-control select2 update-field" style="width: 100%;">
                                    <option disabled selected>Select</option>
                                    <option value="{{ $employee->{$p . 'brgy'} ?? '' }}" {!! $col($p . 'brgy') !!} selected>
                                        {{ (isset($employee->{$p . 'brgy'}) && $places['barangay']) ? $places['barangay']->name : 'N/A' }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6 col-xl-3 dtr-field">
                                <label class="dtr-label" for="{{ $p }}block">House / Block / Lot No.</label>
                                <input type="text" name="{{ $p }}block" id="{{ $p }}block" value="{{ $employee->{$p . 'block'} }}" {!! $col($p . 'block') !!} class="form-control update-field" placeholder="N/A">
                            </div>
                            <div class="col-md-6 col-xl-3 dtr-field">
                                <label class="dtr-label" for="{{ $p }}street">Street</label>
                                <input type="text" name="{{ $p }}street" id="{{ $p }}street" value="{{ $employee->{$p . 'street'} }}" {!! $col($p . 'street') !!} class="form-control update-field" placeholder="N/A">
                            </div>
                            <div class="col-md-6 col-xl-3 dtr-field">
                                <label class="dtr-label" for="{{ $p }}village">Subdivision / Village</label>
                                <input type="text" name="{{ $p }}village" id="{{ $p }}village" value="{{ $employee->{$p . 'village'} }}" {!! $col($p . 'village') !!} class="form-control update-field" placeholder="N/A">
                            </div>
                            <div class="col-md-6 col-xl-3 dtr-field">
                                <label class="dtr-label" for="{{ $p }}zcode">ZIP code</label>
                                <input type="number" name="{{ $p }}zcode" id="{{ $p }}zcode" value="{{ $employee->{$p . 'zcode'} }}" {!! $col($p . 'zcode') !!} class="form-control update-field" placeholder="N/A">
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </form>
        </div>
    </div>
</div>
@endsection
