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
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('template/plugins/toastr/toastr.min.css') }}">
    <!-- Logo  -->
    <link rel="shortcut icon" type="" href="{{ asset('template/img/CPSU_L.png') }}">
    <style>
        :root {
            --green-900: #0b3d24;
            --green-700: #146a3b;
            --green-600: #187744;
            --gold-500: #f2c811;
            --gold-400: #ffcb2c;
            --ink-900: #1c2b24;
            --ink-600: #56655d;
            --line: #e4ebe7;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: 'Source Sans Pro', -apple-system, 'Segoe UI', Roboto, sans-serif;
            color: var(--ink-900);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px 16px;
            background:
                linear-gradient(rgba(11, 61, 36, .86), rgba(11, 61, 36, .86)),
                url('{{ asset('template/img/login-bg.jpg') }}') center / cover no-repeat fixed;
        }
        .login-shell {
            width: 100%;
            max-width: 400px;
        }
        .login-card {
            background: rgba(255, 255, 255, .97);
            border-radius: 10px;
            padding: 36px 32px 28px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, .25);
            text-align: center;
        }
        .login-logo {
            width: 96px;
            height: 96px;
            margin: 0 auto 16px;
        }
        .login-logo img { width: 100%; height: 100%; display: block; }
        .login-title {
            margin: 0;
            font-size: 26px;
            font-weight: 700;
            color: var(--ink-900);
        }
        .login-title span { color: var(--green-600); }
        .login-sub {
            margin: 6px 0 26px;
            color: var(--ink-600);
            font-size: 15px;
        }
        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 12px 16px;
            border-radius: 6px;
            border: 1px solid #c9d3cd;
            background: #fff;
            color: var(--ink-900);
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color .15s ease;
        }
        .btn-google:hover {
            background: #f2f5f3;
            color: var(--ink-900);
        }
        .btn-google:focus-visible {
            outline: 3px solid var(--gold-500);
            outline-offset: 2px;
        }
        .btn-google svg { width: 20px; height: 20px; flex: 0 0 20px; }
        .login-foot {
            margin-top: 22px;
            text-align: center;
            font-size: 13px;
            color: rgba(255, 255, 255, .85);
        }
        @media (max-width: 400px) {
            .login-card { padding: 30px 22px 24px; }
        }
    </style>
</head>
<body>
    <main class="login-shell">
        <div class="login-card">
            <div class="login-logo">
                <a href="./"><img src="{{ asset('template/img/CPSU_L.png') }}" alt="CPSU Logo"></a>
            </div>
            <h1 class="login-title">CPSU <span>HRIS</span></h1>
            <p class="login-sub">Human Resource Information System</p>

            <a href="{{ route('google.login') }}" class="btn-google">
                <svg viewBox="0 0 48 48" aria-hidden="true">
                    <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/>
                    <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>
                    <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z"/>
                    <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/>
                </svg>
                Sign in with Google
            </a>
        </div>
        <div class="login-foot">&copy; {{ now()->year }} Central Philippines State University</div>
    </main>

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
