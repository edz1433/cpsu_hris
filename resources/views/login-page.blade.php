<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>CPSU | HRIS</title>
        <!-- Google Font: Source Sans Pro -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,600,700&display=fallback">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free-v6/css/all.min.css') }}">
        <!-- icheck bootstrap -->
        <link rel="stylesheet" href="{{ asset('template/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
        <!-- Theme style -->
        <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.css') }}">
        <!-- Logo  -->
        <link rel="shortcut icon" type="" href="{{ asset('template/img/CPSU_L.png') }}">

        <style>
            :root {
                --green-900: #0b3d24;
                --green-800: #0f5a32;
                --green-700: #146a3b;
                --green-600: #187744;
                --green-50:  #f2f8f4;
                --gold-500:  #f2c811;
                --gold-400:  #ffcb2c;
                --ink-900:   #1c2b24;
                --ink-600:   #56655d;
                --ink-400:   #8a978f;
                --line:      #dfe7e2;
            }
            html, body { height: 100%; }
            body {
                margin: 0;
                background: #f4f7f5 !important;
                font-family: 'Source Sans Pro', -apple-system, 'Segoe UI', Roboto, sans-serif;
                color: var(--ink-900);
            }
            .auth {
                display: flex;
                min-height: 100vh;
            }

            /* Brand panel */
            .auth-brand {
                position: relative;
                flex: 1 1 58%;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                padding: 48px 56px;
                color: #fff;
                overflow: hidden;
                background:
                    linear-gradient(rgba(11, 61, 36, .88), rgba(11, 61, 36, .88)),
                    url({{ asset('template/img/login-bg.jpg') }}) center / cover no-repeat;
            }
            .auth-brand .brand-row {
                display: flex;
                align-items: center;
                gap: 12px;
                font-weight: 700;
                font-size: 18px;
            }
            .auth-brand .brand-row img {
                width: 44px;
                height: 44px;
                border-radius: 50%;
                background: #fff;
                padding: 2px;
            }
            .auth-brand h2 {
                font-size: 34px;
                line-height: 1.15;
                font-weight: 700;
                max-width: 520px;
                margin: 0 0 14px;
            }
            .auth-brand small { color: rgba(255, 255, 255, .7); position: relative; z-index: 1; }

            /* Form panel */
            .auth-form {
                flex: 1 1 42%;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 40px 24px;
            }
            .auth-card {
                width: 100%;
                max-width: 380px;
            }
            .auth-card .auth-logo {
                width: 84px;
                height: 84px;
                margin-bottom: 18px;
            }
            .auth-card .auth-logo img { width: 100%; height: 100%; display: block; }
            .auth-card h1 {
                font-size: 28px;
                font-weight: 700;
                margin: 0 0 4px;
                color: var(--ink-900);
            }
            .field { margin-bottom: 16px; }
            .field label {
                display: block;
                font-size: 13px;
                font-weight: 600;
                color: var(--ink-600);
                margin-bottom: 6px;
            }
            .field-control {
                position: relative;
            }
            .field-control > .lead-icon {
                position: absolute;
                left: 14px;
                top: 50%;
                transform: translateY(-50%);
                color: var(--ink-400);
                pointer-events: none;
            }
            .field-control .form-control {
                height: 46px;
                padding-left: 42px;
                padding-right: 44px;
                border-radius: 6px;
                border: 1px solid #c9d3cd;
                background: #fff;
                color: var(--ink-900);
                font-size: 15px;
                transition: border-color .15s ease;
            }
            .field-control .form-control:focus {
                border-color: var(--green-600);
                box-shadow: 0 0 0 1px var(--green-600);
            }
            .field-control .form-control:focus ~ .lead-icon { color: var(--green-600); }
            .field-control .trail-btn {
                position: absolute;
                right: 6px;
                top: 50%;
                transform: translateY(-50%);
                width: 36px;
                height: 36px;
                border-radius: 4px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: var(--ink-400);
                cursor: pointer;
            }
            .field-control .trail-btn:hover { background: var(--green-50); color: var(--green-600); }
            .btn-login {
                width: 100%;
                height: 46px;
                border: 0;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 600;
                color: #fff;
                background: var(--green-600);
                transition: background-color .15s ease;
            }
            .btn-login:hover {
                color: #fff;
                background: var(--green-800);
            }
            .btn-login:focus-visible {
                outline: 3px solid var(--gold-500);
                outline-offset: 2px;
            }
            .auth-card .alert {
                border-radius: 6px;
                font-size: 14px;
                border: 0;
            }
            .auth-card .alert-danger { background: #fdecea; color: #a5281b; }
            .auth-card .alert-success { background: #e3f2e9; color: var(--green-800); }

            @media (max-width: 991.98px) {
                .auth-brand { display: none; }
                .auth-card { text-align: center; }
                .auth-card .auth-logo { margin-left: auto; margin-right: auto; }
                .auth-card form { text-align: left; }
            }
        </style>
    </head>
    <body class="hold-transition">
        <div class="auth">
            <section class="auth-brand">
                <div class="brand-row">
                    <img src="{{ asset('template/img/CPSU_L.png') }}" alt="CPSU Logo">
                    CPSU HRIS
                </div>
                <div style="position: relative; z-index: 1;">
                    <h2>Human Resource Information System</h2>
                </div>
                <small>&copy; {{ now()->year }} Central Philippines State University</small>
            </section>

            <section class="auth-form">
                <div class="auth-card">
                    <a href="./" class="d-inline-block d-lg-none auth-logo">
                        <img src="{{ asset('template/img/CPSU_L.png') }}" alt="CPSU Logo">
                    </a>
                    <h1 class="mb-4">HR Admin Login</h1>

                    <form action="{{ route('postLogin') }}" method="post" id="signInAuth">
                        @csrf

                        @if(session('error'))
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle mr-1"></i> {{session('error')}}
                            </div>
                        @endif

                        @if(session('success'))
                            <div class="alert alert-success">
                                <i class="fas fa-check mr-1"></i> {{session('success')}}
                            </div>
                        @endif

                        <div class="field">
                            <label for="username">Username</label>
                            <div class="field-control">
                                <input type="text" class="form-control" name="username" id="username" autocomplete="off" autofocus>
                                <span class="fas fa-user lead-icon"></span>
                            </div>
                        </div>
                        <div class="field">
                            <label for="password">Password</label>
                            <div class="field-control">
                                <input type="password" class="form-control" name="password" id="password" autocomplete="off">
                                <span class="fas fa-lock lead-icon"></span>
                                <span class="trail-btn" title="Show / hide password">
                                    <span class="fas fa-eye" id="togglePassword"></span>
                                </span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-login mt-2">Log In</button>
                    </form>

                </div>
            </section>
        </div>

        <!-- jQuery -->
        <script src="{{ asset('template/plugins/jquery/jquery.min.js') }}"></script>
        <!-- Bootstrap 4 -->
        <script src="{{ asset('template/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
        <!-- AdminLTE App -->
        <script src="{{ asset('template/dist/js/adminlte.min.js') }}"></script>
        <!-- jquery-validation -->
        <script src="{{ asset('template/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
        <script src="{{ asset('template/plugins/jquery-validation/additional-methods.min.js') }}"></script>

        <script>
            const togglePassword = document.getElementById('togglePassword');
            const password = document.getElementById('password');

            togglePassword.parentElement.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                togglePassword.classList.toggle('fa-eye-slash');
            });
        </script>
    </body>
</html>
