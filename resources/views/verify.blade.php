<!DOCTYPE html>
<html lang="en">
    @php
        // if (!session()->has('email')) {
        //     return redirect()->back();
        // }
    @endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Verify Your Email</title>
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free-v6/css/all.min.css') }}">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="{{ asset('template/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.css') }}">
    <!-- Logo  -->
    <link rel="shortcut icon" href="{{ asset('template/img/CPSU_L.png') }}">
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
            max-width: 350px;
            margin: 0 auto;
        }
        .card {
            background-color: rgba(76, 69, 69, 0.5);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .login-logo img {
            width: 40%;
        }
        .login-box-msg {
            color: #358359;
            font-size: 1.1rem;
            font-weight: 600;
            text-align: center;
        }
        .input-group-text {
            background-color: #6c757d;
            border: none;
        }
        .form-control {
            border-left: none;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="card">
            <div class="card-body">
                <div class="login-logo mt-4">
                    <a href="./">
                        <img src="{{ asset('template/img/CPSU_L.png') }}" class="img-responsive">
                    </a>
                </div>
                <p class="login-box-msg">
                    <span class="text-light">Verify Your Email</span>
                </p>
                <form action="{{ route('verify.code') }}" method="POST">
                    @csrf
                    <input type="hidden" name="email" value="{{ session('email') }}">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-key"></i>
                            </span>
                        </div>
                        <input type="text" id="code" class="form-control" name="verification_code" autocomplete="off" placeholder="Verification Code" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-secondary btn-block">Verify</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
