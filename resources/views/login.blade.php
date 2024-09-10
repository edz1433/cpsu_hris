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
    <!-- Logo  -->
    <link rel="shortcut icon" type="" href="{{ asset('template/img/CPSU_L.png') }}">
    <style>
        body {
            background-image: url('{{ asset('template/img/login-bg.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            width: 100% !important;
            max-width: 300px;
            margin: 0 auto;
            height: 300px;
        }
        .card {
            background-color: rgba(76, 69, 69, 0.5); /* White background with 50% opacity */
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(0, 0, 0, 0.1); /* Optional: subtle border */
            height: 300px;
            padding: 0px !important;
        }
        .card-body {
            background-color: transparent; /* Make sure the inner content is fully opaque */
            padding: 5px; /* Ensure padding inside card */
            height: 300px;
        }
        .login-logo img {
            width: 40%;
        }
        .login-box-msg {
            color: #358359;
            font-size: 1rem;
            font-weight: 600;
            text-align: center; /* Center the message text */
        }
        .text-center a {
            display: inline-block;
            width: 55%;
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
    <div id="loom-companion-mv3" ext-id="liecbddmkiiihnedobmlmillhodjkdmb"><section id="shadow-host-companion"></section></div>
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