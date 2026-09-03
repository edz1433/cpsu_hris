<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0c3b29">
    <title>CPSU HRIS | Under Maintenance</title>
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free-v6/css/all.min.css') }}">
    <link rel="shortcut icon" href="{{ asset('template/img/CPSU_L.png') }}">
    <style>
        :root {
            --cpsu-green: #187744;
            --cpsu-green-dark: #0c3b29;
            --cpsu-green-deep: #06291c;
            --cpsu-gold: #ffcb2c;
            --ink: #17251e;
            --muted: #66746c;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html, body { min-height: 100%; }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            background:
                linear-gradient(120deg, rgba(5, 35, 24, .96), rgba(12, 59, 41, .82)),
                url('{{ asset('template/img/login-bg.jpg') }}') center/cover fixed no-repeat;
            color: var(--ink);
            font-family: Inter, ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .ambient-glow {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            filter: blur(8px);
            opacity: .38;
        }

        .ambient-glow--gold {
            width: 340px;
            height: 340px;
            top: -180px;
            right: -80px;
            background: radial-gradient(circle, rgba(255, 203, 44, .7), rgba(255, 203, 44, 0) 70%);
        }

        .ambient-glow--green {
            width: 420px;
            height: 420px;
            bottom: -250px;
            left: -130px;
            background: radial-gradient(circle, rgba(49, 184, 112, .6), rgba(49, 184, 112, 0) 70%);
        }

        .maintenance-shell {
            position: relative;
            z-index: 1;
            width: min(100%, 980px);
            min-height: 570px;
            display: grid;
            grid-template-columns: 43% 57%;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .28);
            border-radius: 28px;
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 36px 90px rgba(0, 18, 11, .38), 0 3px 10px rgba(0, 0, 0, .12);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .brand-panel {
            position: relative;
            isolation: isolate;
            padding: 44px 42px 38px;
            display: flex;
            flex-direction: column;
            color: #fff;
            background: url('{{ asset('template/img/login-bg.jpg') }}') 36% center/cover no-repeat;
        }

        .brand-panel::before {
            position: absolute;
            z-index: -1;
            inset: 0;
            content: "";
            background:
                linear-gradient(180deg, rgba(6, 41, 28, .63), rgba(6, 41, 28, .94)),
                linear-gradient(135deg, rgba(24, 119, 68, .46), transparent 60%);
        }

        .brand-panel::after {
            position: absolute;
            z-index: -1;
            right: -80px;
            bottom: -95px;
            width: 260px;
            height: 260px;
            content: "";
            border: 1px solid rgba(255, 203, 44, .22);
            border-radius: 50%;
            box-shadow: 0 0 0 36px rgba(255, 203, 44, .05), 0 0 0 78px rgba(255, 203, 44, .035);
        }

        .brand-mark {
            width: 86px;
            height: 86px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255, 255, 255, .52);
            border-radius: 24px;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 18px 38px rgba(0, 0, 0, .24);
        }

        .brand-mark img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }

        .brand-copy { margin-top: auto; }

        .brand-eyebrow {
            display: block;
            margin-bottom: 14px;
            color: var(--cpsu-gold);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .16em;
            line-height: 1.5;
            text-transform: uppercase;
        }

        .brand-copy h2 {
            max-width: 310px;
            margin: 0;
            font-size: clamp(1.8rem, 3vw, 2.45rem);
            font-weight: 700;
            letter-spacing: -.045em;
            line-height: 1.08;
        }

        .brand-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, .18);
            color: rgba(255, 255, 255, .67);
            font-size: .77rem;
            letter-spacing: .03em;
        }

        .status-panel {
            position: relative;
            padding: 54px 56px 46px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: radial-gradient(circle at 100% 0, rgba(255, 203, 44, .1), transparent 32%), #fff;
        }

        .status-badge {
            align-self: flex-start;
            margin-bottom: 31px;
            padding: 9px 14px;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            border: 1px solid #e5eadf;
            border-radius: 999px;
            background: #f8faf7;
            color: #526159;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .status-dot {
            position: relative;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #e4a900;
            box-shadow: 0 0 0 4px rgba(228, 169, 0, .14);
        }

        .status-dot::after {
            position: absolute;
            inset: -5px;
            content: "";
            border: 1px solid rgba(228, 169, 0, .45);
            border-radius: inherit;
            animation: statusPulse 2.1s ease-out infinite;
        }

        .service-icon {
            width: 62px;
            height: 62px;
            margin-bottom: 24px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(24, 119, 68, .12);
            border-radius: 19px;
            background: linear-gradient(145deg, #eff9f3, #fff);
            box-shadow: 0 12px 30px rgba(24, 119, 68, .1);
            color: var(--cpsu-green);
            font-size: 1.45rem;
        }

        .status-panel h1 {
            max-width: 450px;
            margin: 0;
            color: var(--ink);
            font-size: clamp(2.45rem, 5vw, 4rem);
            font-weight: 750;
            letter-spacing: -.06em;
            line-height: .98;
        }

        .status-panel h1 span {
            display: block;
            color: var(--cpsu-green);
        }

        .lead {
            max-width: 455px;
            margin: 24px 0 0;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.75;
        }

        .status-details {
            margin-top: 32px;
            padding-top: 25px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            border-top: 1px solid #e9eee9;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 11px;
            color: #4e5d55;
            font-size: .78rem;
            font-weight: 650;
            line-height: 1.35;
        }

        .detail-icon {
            flex: 0 0 auto;
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: #f0f7f2;
            color: var(--cpsu-green);
        }

        .reference {
            position: absolute;
            right: 24px;
            bottom: 19px;
            color: #b4bdb7;
            font-size: .65rem;
            font-weight: 750;
            letter-spacing: .13em;
            text-transform: uppercase;
        }

        @keyframes statusPulse {
            0% { opacity: .85; transform: scale(.55); }
            80%, 100% { opacity: 0; transform: scale(1.45); }
        }

        @media (max-width: 780px) {
            body { padding: 22px; align-items: flex-start; }
            .maintenance-shell { grid-template-columns: 1fr; min-height: 0; border-radius: 23px; }
            .brand-panel { min-height: 185px; padding: 25px 27px; background-position: center 45%; }
            .brand-mark { width: 68px; height: 68px; border-radius: 19px; }
            .brand-mark img { width: 57px; height: 57px; }
            .brand-copy { margin-top: 34px; }
            .brand-eyebrow { margin-bottom: 8px; font-size: .64rem; }
            .brand-copy h2 { font-size: 1.35rem; }
            .brand-footer { display: none; }
            .status-panel { padding: 34px 28px 42px; }
            .status-badge { margin-bottom: 24px; }
            .service-icon { width: 53px; height: 53px; margin-bottom: 20px; border-radius: 16px; }
            .status-panel h1 { font-size: clamp(2.25rem, 11vw, 3.1rem); }
            .lead { margin-top: 19px; font-size: .94rem; line-height: 1.65; }
            .status-details { grid-template-columns: 1fr; margin-top: 25px; padding-top: 20px; }
        }

        @media (max-width: 420px) {
            body { padding: 12px; }
            .maintenance-shell { border-radius: 19px; }
            .brand-panel { min-height: 165px; padding: 21px 22px; }
            .status-panel { padding: 29px 22px 40px; }
            .reference { right: 18px; bottom: 15px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .status-dot::after { animation: none; }
        }
    </style>
</head>
<body>
    <div class="ambient-glow ambient-glow--gold" aria-hidden="true"></div>
    <div class="ambient-glow ambient-glow--green" aria-hidden="true"></div>

    <main class="maintenance-shell" role="main">
        <section class="brand-panel" aria-label="CPSU HRIS">
            <div class="brand-mark">
                <img src="{{ asset('template/img/CPSU_L.png') }}" alt="Central Philippines State University seal">
            </div>

            <div class="brand-copy">
                <span class="brand-eyebrow">Central Philippines State University</span>
                <h2>Human Resource Information System</h2>
            </div>

            <div class="brand-footer">Kabankalan City, Negros Occidental</div>
        </section>

        <section class="status-panel">
            <div class="status-badge">
                <span class="status-dot" aria-hidden="true"></span>
                System under maintenance
            </div>

            <div class="service-icon" aria-hidden="true">
                <i class="fas fa-screwdriver-wrench"></i>
            </div>

            <h1>We're making <span>HRIS better.</span></h1>
            <p class="lead">
                The system is temporarily unavailable while our team performs essential maintenance. Thank you for your patience. We'll be back online as soon as possible.
            </p>

            <div class="status-details">
                <div class="detail-item">
                    <span class="detail-icon"><i class="fas fa-shield-halved" aria-hidden="true"></i></span>
                    <span>Your information remains secure</span>
                </div>
                <div class="detail-item">
                    <span class="detail-icon"><i class="far fa-clock" aria-hidden="true"></i></span>
                    <span>Please check back again shortly</span>
                </div>
            </div>

            <span class="reference">Service status &bull; 503</span>
        </section>
    </main>
</body>
</html>
