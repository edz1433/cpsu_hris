<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CPSU HRIS {{ isset($title) ? ' | '.$title : '' }}</title>
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free-v6/css/all.min.css') }}">
    <!-- fullCalendar -->
    <link rel="stylesheet" href="{{ asset('template/plugins/fullcalendar/main.css') }}">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="{{ asset('template/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('template/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('template/plugins/toastr/toastr.min.css') }}">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="{{ asset('template/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <!-- Custom style -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/style.css') }}">
    <!-- QR -->
    <script src="{{ asset('template/dist/js/html2canvas.min.js') }}"></script>
    <script src="{{ asset('template/dist/js/qrcode.min.js') }}"></script>
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('template/img/CPSU_L.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
    .profile-image {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        margin-top: -7px;
        margin-right: 10px;
    }
    .img-circle1 {
        width: 40px !important;
        height: 40px !important;
        border-radius: 50% !important;
        object-fit: cover !important;
        border: 2px solid #ddd !important;
        display: block !important;
    }

    .nav-item.dropdown .dropdown-menu.notifications{
        width: 500px !important; /* Or whatever width you prefer */
        max-width: none !important; /* Ensure it doesn't get constrained by max-width */
    }
    .btn-success1 {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
    }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed sidebar-collapse layout-navbar-fixed text-sm">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-warning">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars text-success1"></i></a>
                </li>
            </ul>
            
            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                @if($guard == "web")
                    @include('layouts.notif-admin')
                @else
                    @include('layouts.notif-employee')
                @endif
                
                <!-- User Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-success1" href="#" role="button" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        @php
                            $profileUrl = asset('Profile/Employee/' . auth()->guard($guard)->user()->profile);
                            $profilePath = public_path('Profile/Employee/' . auth()->guard($guard)->user()->profile);
                        @endphp
                        <img src="{{ file_exists($profilePath) && isset(auth()->guard($guard)->user()->profile) ? $profileUrl : asset('Profile/Employee/default.png') }}" alt="User Image" class="profile-image">
                    </a>                    
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
                        {{-- <a class="dropdown-item" href="{{ route('myAccount') }}"><i class="fas fa-key fa-xs"></i> My Account</a> --}}
                        <a class="dropdown-item" href="{{ route('logout') }}"><i class="fas fa-power-off fa-xs"></i> Sign Out</a>
                    </div>
                </li>
            </ul>
        </nav>
        
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dim-green elevation-2">
            <!-- Brand Logo -->
            <a href="#" class="brand-link">
                <img src="{{ asset('template/img/CPSU_L.png') }}" alt="AdminLTE Logo" class="brand-image img-circle">
                <span class="brand-text font-weight-bold text-success1">CPSU HRIS</span>
            </a>        

            <!-- Sidebar -->
            <div class="sidebar">
                <hr class="sidebar-divider">
                <!-- Sidebar user panel (optional) -->
                <div class="user-panel mt-4 d-flex">
                    <div class="image">
                        @php
                            $profileUrl = asset('Profile/Employee/' . auth()->guard($guard)->user()->profile);
                            $profilePath = public_path('Profile/Employee/' . auth()->guard($guard)->user()->profile);
                        @endphp
                        <img src="{{ file_exists($profilePath) && auth()->guard($guard)->check() && auth()->guard($guard)->user()->profile ? $profileUrl : asset('Profile/Employee/default.png') }}" 
                             class="img-circle1 elevation-2" 
                             alt="User Image">
                    </div>                    
                    <div class="info ml-2" style="margin-top: -7px;">
                        <span class="d-block">
                            {{ ucwords(strtolower(auth()->guard($guard)->user()->fname)) }} {{ ucwords(strtolower(auth()->guard($guard)->user()->lname)) }}
                        </span>
                        <span class="d-block text-sm text-muted">
                            @if($guard == "employee")
                                {{ auth()->guard($guard)->user()->emp_status == 1 ? auth()->guard($guard)->user()->position : 'Employee' }}
                            @else
                                {{ ucfirst(auth()->guard($guard)->user()->role) }}
                            @endif
                        </span>
                    </div>
                </div>                
                <hr>
                <!-- Sidebar Menu -->
                @include('partials.control')
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper" style="padding-top: 20px;">
            <!-- Main content -->
            <div class="content">
                @yield('body')
            </div>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->
        
        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->

        <!-- Privacy Modal -->
        <div id="dataPrivacyModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="dataPrivacyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-body p-0">
                        <iframe
                            id="privacy-iframe"
                            src=""
                            frameborder="0"
                            width="100%"
                            height="600"
                            style="display: block;"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Footer -->
        <footer class="main-footer" style="padding: 15px 20px; background-color: #f8f9fa; border-top: 1px solid #dee2e6; font-size: 14px; color: #495057;">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center;">
                <div>
                    <strong>All rights reserved.</strong>
                    &nbsp;|&nbsp;
                    <a href="#" id="openPrivacyPolicy" data-toggle="modal" data-target="#dataPrivacyModal" style="text-decoration: none; color: #007bff;">
                        Data Privacy Policy
                    </a>
                </div>
                <div class="d-none d-sm-inline" style="margin-top: 5px;">
                    Maintained and Managed by 
                    <a href="https://www.facebook.com/cpsumiso.main" target="_blank" style="text-decoration: none; color: #007bff;">
                        MIS
                    </a>.
                </div>
            </div>
        </footer>


    </div>

@include('script.masterScript')
@include('script.driveScript')
@include('script.officeScript')
@if(request()->is('pds/family-bg/*') || request()->is('pds/family-bg'))
    @include('script.familybgScript')
@endif
@if(request()->is('employees') || request()->is('employees/*'))
    @include('script.employeeScript')
@endif
@if(request()->is('user') || request()->is('user/*'))
    @include('script.userScript')
@endif
@if(request()->is('pds') || request()->is('pds/personal-info') || request()->is('pds/personal-info/*'))
    @include('script.personInfoScript')
@endif
@if(request()->is('pds/educ-bg/*') || request()->is('pds/educ-bg'))
    @include('script.educbgScript')
@endif
@if(request()->is('pds/eligibility/*') || request()->is('pds/eligibility') || isset($eligibilityedit))
    @include('script.eligibilityScript')
@endif
@if(request()->is('pds/work-experience/*') || request()->is('pds/work-experience') || isset($workexperienceedit))
    @include('script.WorkExperienceScript')
@endif
@if(request()->is('pds/voluntary-work/*') || request()->is('pds/voluntary-work-edit/*') || request()->is('pds/voluntary-work') || isset($workexperienceedit))
    @include('script.voluntaryWorksScript')
@endif
@if(request()->is('pds/learning-dev/*') || request()->is('pds/learning-dev-edit/*') || request()->is('pds/learning-dev') || isset($learningdevedit))
    @include('script.learningDevScript')
@endif
@if(request()->is('pds/other-info/*') || request()->is('pds/other-info-edit/*') || request()->is('pds/other-info'))
    @include('script.otherInfoScript')
@endif
@if(request()->is('pds/info-question/*') || request()->is('pds/info-question-edit/*') || request()->is('pds/info-question'))
    @include('script.infoquestionScript')
@endif
@if(request()->is('pds/references*'))
    @include('script.referenceScript')
@endif
@if(request()->is('pds/government-id*'))
    @include('script.govidScript')
@endif
@if(request()->is('leaves/*') || request()->is('leaves') || request()->is('leave*') || request()->is('leave/history') || request()->is('leave/history*'))
    @include('script.leaveCreditScript')
@endif
@if(request()->is('pending/*'))
    @include('script.pendingScript')
@endif
@if(request()->is('pds/signature/*') || request()->is('pds/signature'))
    @include('script.signatureScript')
@endif
</body>
</html>
