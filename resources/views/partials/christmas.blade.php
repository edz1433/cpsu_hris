{{-- Christmas decorations: twinkling string lights + snowfall particles.
     Include right after <body>. Remove the @include to turn the theme off. --}}
<style>
    .xmas-snow {
        position: fixed;
        inset: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 0;
    }
    .xmas-lights {
        position: fixed;
        top: -6px;
        left: 0;
        right: 0;
        height: 54px;
        display: flex;
        justify-content: space-around;
        pointer-events: none;
        z-index: 3;
        overflow: hidden;
    }
    .xmas-lights::before {
        content: "";
        position: absolute;
        left: -5%;
        right: -5%;
        top: -14px;
        height: 34px;
        border-bottom: 2px solid rgba(20, 40, 28, .85);
        border-radius: 0 0 50% 50% / 0 0 100% 100%;
    }
    .xmas-lights li {
        position: relative;
        list-style: none;
        width: 10px;
        height: 18px;
        margin-top: 18px;
        border-radius: 50%;
        background: var(--bulb);
        box-shadow: 0 4px 18px 3px var(--bulb);
        animation: xmas-twinkle 1.6s ease-in-out infinite alternate;
    }
    .xmas-lights li::before {
        content: "";
        position: absolute;
        top: -5px;
        left: 2px;
        width: 6px;
        height: 6px;
        border-radius: 2px;
        background: #2a3a30;
    }
    .xmas-lights li:nth-child(4n+1) { --bulb: #ff4b4b; }
    .xmas-lights li:nth-child(4n+2) { --bulb: #ffd23f; animation-delay: .4s; }
    .xmas-lights li:nth-child(4n+3) { --bulb: #4dff9a; animation-delay: .8s; }
    .xmas-lights li:nth-child(4n+4) { --bulb: #9fd8ff; animation-delay: 1.2s; }
    .xmas-lights li:nth-child(odd)  { margin-top: 22px; }
    @keyframes xmas-twinkle {
        from { opacity: 1; }
        to   { opacity: .35; box-shadow: 0 2px 6px 0 var(--bulb); }
    }
    .xmas-greeting {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 5px 14px;
        border-radius: 999px;
        background: linear-gradient(90deg, #b3202a, #d63a3a);
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: .3px;
        box-shadow: 0 4px 12px rgba(179, 32, 42, .3);
    }
    .xmas-greeting i { color: #ffe27a; }
    @media (prefers-reduced-motion: reduce) {
        .xmas-lights li { animation: none; }
    }
</style>

<canvas class="xmas-snow" id="xmasSnow" aria-hidden="true"></canvas>
<ul class="xmas-lights" aria-hidden="true">
    @for ($i = 0; $i < 28; $i++)
        <li></li>
    @endfor
</ul>

<script>
    (function () {
        const canvas = document.getElementById('xmasSnow');
        const ctx = canvas.getContext('2d');
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let flakes = [], w, h, dpr, wind = 0, targetWind = 0;

        function makeFlake(randomY) {
            const depth = Math.random();               // 0 = far, 1 = near
            return {
                x: Math.random() * w,
                y: randomY ? Math.random() * h : -10,
                r: .8 + depth * 2.8,
                speed: .3 + depth * 1.2,
                sway: Math.random() * Math.PI * 2,
                swaySpeed: .005 + Math.random() * .015,
                alpha: .35 + depth * .6,
                sparkle: Math.random() < .08            // a few golden glints
            };
        }

        function resize() {
            dpr = window.devicePixelRatio || 1;
            w = window.innerWidth;
            h = window.innerHeight;
            canvas.width = w * dpr;
            canvas.height = h * dpr;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            const count = Math.min(180, Math.round((w * h) / 9000));
            flakes = Array.from({ length: reduceMotion ? Math.round(count / 3) : count }, () => makeFlake(true));
        }

        function draw() {
            ctx.clearRect(0, 0, w, h);
            for (const f of flakes) {
                ctx.beginPath();
                ctx.arc(f.x, f.y, f.r, 0, Math.PI * 2);
                ctx.fillStyle = f.sparkle
                    ? `rgba(255, 220, 120, ${f.alpha})`
                    : `rgba(255, 255, 255, ${f.alpha})`;
                ctx.shadowBlur = f.sparkle ? 8 : f.r * 1.5;
                ctx.shadowColor = f.sparkle ? '#ffd23f' : '#ffffff';
                ctx.fill();
            }
        }

        function step() {
            wind += (targetWind - wind) * .02;
            for (const f of flakes) {
                f.sway += f.swaySpeed;
                f.y += f.speed;
                f.x += Math.sin(f.sway) * .5 + wind * f.speed;
                if (f.y > h + 10) Object.assign(f, makeFlake(false));
                if (f.x > w + 10) f.x = -10;
                if (f.x < -10) f.x = w + 10;
            }
            draw();
            requestAnimationFrame(step);
        }

        // Moving the mouse nudges the snow sideways.
        window.addEventListener('mousemove', e => { targetWind = (e.clientX / w - .5) * 1.6; });
        window.addEventListener('resize', resize);
        resize();
        reduceMotion ? draw() : requestAnimationFrame(step);
    })();
</script>
