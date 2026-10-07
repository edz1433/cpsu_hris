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
            overflow-x: hidden;
            background:
                radial-gradient(ellipse at 50% 110%, rgba(255, 200, 90, .28), transparent 55%),
                radial-gradient(ellipse at 15% 0%, rgba(214, 58, 58, .22), transparent 45%),
                linear-gradient(160deg, rgba(8, 40, 24, .92), rgba(11, 61, 36, .84) 55%, rgba(6, 30, 18, .94)),
                url('{{ asset('template/img/login-bg.jpg') }}') center / cover no-repeat fixed;
        }
        .login-shell {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 400px;
            animation: login-rise .7s ease-out both;
        }
        @keyframes login-rise {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: none; }
        }
        .login-card {
            position: relative;
            background: rgba(255, 255, 255, .9);
            -webkit-backdrop-filter: blur(10px);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, .6);
            border-radius: 16px;
            padding: 44px 32px 30px;
            box-shadow:
                0 24px 48px rgba(0, 0, 0, .35),
                0 0 0 6px rgba(255, 255, 255, .06);
            text-align: center;
        }
        /* Snow resting on top of the card */
        .login-card::before {
            content: "";
            position: absolute;
            top: -16px;
            left: 10px;
            right: 10px;
            height: 26px;
            background:
                linear-gradient(#fff, #fff) 0 100% / 100% 8px no-repeat,
                radial-gradient(circle at 8% 100%, #fff 14px, transparent 15px),
                radial-gradient(circle at 22% 90%, #fff 18px, transparent 19px),
                radial-gradient(circle at 40% 100%, #fff 15px, transparent 16px),
                radial-gradient(circle at 58% 85%, #fff 20px, transparent 21px),
                radial-gradient(circle at 76% 100%, #fff 15px, transparent 16px),
                radial-gradient(circle at 92% 92%, #fff 17px, transparent 18px);
            filter: drop-shadow(0 -2px 3px rgba(0, 0, 0, .12));
            pointer-events: none;
        }
        .login-card .xmas-greeting { margin-bottom: 18px; }
        /* Santa hat hanging on the card's top-left corner */
        .xmas-hat {
            position: absolute;
            top: -76px;
            left: -50px;
            width: 170px;
            transform: rotate(-37deg);
            transform-origin: 60.8% 89.5%;  /* centre of the brim's bottom edge */
            filter: drop-shadow(0 10px 12px rgba(0, 0, 0, .35));
            pointer-events: none;
            z-index: 2;
            animation: xmas-hat-sway 4s ease-in-out infinite;
        }
        @keyframes xmas-hat-sway {
            0%, 100% { transform: rotate(-37deg); }
            50%      { transform: rotate(-33deg); }
        }
        @media (prefers-reduced-motion: reduce) {
            .xmas-hat { animation: none; }
        }
        .login-logo {
            width: 96px;
            height: 96px;
            margin: 0 auto 16px;
            border-radius: 50%;
            box-shadow: 0 0 0 6px rgba(242, 200, 17, .18), 0 0 28px rgba(242, 200, 17, .35);
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
            transition: background-color .15s ease, transform .15s ease, box-shadow .15s ease;
        }
        .btn-google:hover {
            background: #fff;
            color: var(--ink-900);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(11, 61, 36, .18);
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
            text-shadow: 0 1px 2px rgba(0, 0, 0, .4);
        }
        @media (max-width: 400px) {
            .login-card { padding: 30px 22px 24px; }
        }
        @media (max-width: 560px) {
            .login-shell { margin-top: 40px; max-width: calc(100% - 56px); }
            .xmas-hat { width: 120px; top: -55px; left: -36px; }
        }
    </style>
</head>
<body>
    @include('partials.christmas')

    <main class="login-shell">
        <div class="login-card">
            <svg class="xmas-hat" viewBox="0 0 260 210" aria-hidden="true">
                <defs>
                    <linearGradient id="hatRed" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="#ff4a4a"/>
                        <stop offset=".55" stop-color="#d81b26"/>
                        <stop offset="1" stop-color="#8e0c14"/>
                    </linearGradient>
                    <linearGradient id="hatTip" x1="1" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#c3141f"/>
                        <stop offset="1" stop-color="#7a0910"/>
                    </linearGradient>
                    <radialGradient id="hatFur" cx=".4" cy=".3" r=".8">
                        <stop offset="0" stop-color="#ffffff"/>
                        <stop offset=".7" stop-color="#eef0f2"/>
                        <stop offset="1" stop-color="#c9cdd2"/>
                    </radialGradient>
                    <filter id="hatFluff" x="-20%" y="-30%" width="140%" height="160%">
                        <feTurbulence type="fractalNoise" baseFrequency=".85" numOctaves="2" seed="3" result="noise"/>
                        <feDisplacementMap in="SourceGraphic" in2="noise" scale="9" xChannelSelector="R" yChannelSelector="G"/>
                    </filter>
                </defs>
                <!-- body -->
                <path d="M74 160 C 80 80, 122 12, 178 10 C 226 8, 244 80, 248 160 Z" fill="url(#hatRed)"/>
                <!-- crease highlight -->
                <path d="M130 150 C 138 100, 168 52, 208 40" stroke="rgba(255,255,255,.18)" stroke-width="6" fill="none" stroke-linecap="round"/>
                <!-- floppy tip -->
                <path d="M184 12 C 116 2, 60 52, 34 150 L 58 156 C 80 84, 122 42, 186 40 Z" fill="url(#hatTip)"/>
                <!-- brim -->
                <rect x="58" y="138" width="200" height="50" rx="25" fill="url(#hatFur)" filter="url(#hatFluff)"/>
                <!-- pompom -->
                <circle cx="40" cy="158" r="30" fill="url(#hatFur)" filter="url(#hatFluff)"/>
            </svg>
            <span class="xmas-greeting"><i class="fas fa-star"></i> Merry Christmas! <i class="fas fa-gift"></i></span>
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
