<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>CPSU | HRIS</title>
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free-v6/css/all.min.css') }}">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="{{ asset('template/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('template/plugins/toastr/toastr.min.css') }}">
    <!-- Login style -->
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <!-- Logo  -->
    <link rel="shortcut icon" type="" href="{{ asset('template/img/CPSU_L.png') }}">
    <style>
        body{
            background-image: url('{{ asset('template/img/login-bg.jpg') }}');
        }
    </style>
</head>
<body class="login-page">
    <div class="login-box">
        <div class="card">
            <div class="card-body">
                <div class="login-logo mt-4">
                    <a href="./">
                        <img src="{{ asset('template/img/CPSU_L.png') }}" class="img-responsive">
                    </a>
                </div>
                <p class="login-box-msg" style="font-size: 12pt;">
                    <span class="text-light">Welcome to <b style="color: #FFCB2C;">CPSU HRIS</b>
                </p>  
                <div class="text-center">
                    <a href="{{ route('google.login') }}">
                        <img src="{{ asset('template/img/google-signin.png') }}" class="img-responsive" style="width: 130%; margin-left: -20px;">
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- jQuery -->
    <script src="{{ asset('template/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('template/plugins/toastr/toastr.min.js') }}"></script>
    @if(session('error'))
        <script>
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-bottom-center",
                "timeOut": "3000",
            };
            toastr.error("{{ session('error') }}");
        </script>
    @endif
        
</body>
</html>