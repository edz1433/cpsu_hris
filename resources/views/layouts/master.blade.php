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
    <link rel="stylesheet" href="{{ asset('template/plugins/fullcalendar/fullcalendar.css') }}">
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
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('template/img/CPSU_L.png') }}">
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
                @if(auth()->guard($guard)->user()->role !== "employee")
                <!-- Search Form -->
                <li class="nav-item">
                    <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                        <i class="fas fa-search text-success1"></i>
                    </a>
                    <div class="navbar-search-block" style="width: 92%">
                        <form class="form-inline">
                            <div class="input-group">
                                <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
                                <div class="input-group-append">
                                    <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                                        <i class="fas fa-times text-success1"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" role="button">
                        <i class="fas fa-bell text-success1"></i>
                    </a>
                </li>
                @endif
                <!-- User Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-success1" href="#" role="button" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        @if (auth()->guard($guard)->check() && auth()->guard($guard)->user()->profile)
                            <img src="{{ asset('Profile/Employee/' . auth()->guard($guard)->user()->profile) }}" alt="User Image" class="profile-image">
                        @else
                            <img src="{{ asset('Profile/Employee/default.png') }}" alt="User Image" class="profile-image">
                        @endif
                    </a>                    
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
                        <a class="dropdown-item" href="{{ route('myAccount') }}"><i class="fas fa-key fa-xs"></i> My Account</a>
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
                        @if (auth()->guard($guard)->check() && auth()->guard($guard)->user()->profile)
                            <img src="{{ asset('Profile/Employee/' . auth()->guard($guard)->user()->profile) }}" class="img-circle1 elevation-2" alt="User Image">
                        @else
                            <img src="{{ asset('Profile/Employee/default.png') }}" class="img-circle1 elevation-2" alt="User Image">
                        @endif
                    </div>                    
                    <div class="info ml-2" style="margin-top: -7px;">
                        <span class="d-block">{{ ucwords(strtolower(auth()->guard($guard)->user()->fname)) }} {{ ucwords(strtolower(auth()->guard($guard)->user()->lname)) }}                        </span>
                        <span class="d-block text-sm text-muted">
                        @if($guard == "employee")
                            {{ (auth()->guard($guard)->user()->emp_status == 1)  ? auth()->guard($guard)->user()->position : 'Employee' }}
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

        <!-- Main Footer -->
        <footer class="main-footer">
            <div class="float-right d-none d-sm-inline">
                Maintain and Manage by <a href="#">MIS</a>.
            </div>
            <strong>All rights reserved.</strong>
        </footer>
    </div>

@include('script.masterScript')
@include('script.driveScript')
@include('script.officeScript')
</body>
</html>
