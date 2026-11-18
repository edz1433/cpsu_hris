{{-- Web push subscription for signed-in pages (layouts/master). Needs partials/pwa (registers sw.js). --}}
@if(\App\Services\WebPush\PushSender::enabled())
@php
    $setupUser = auth()->guard('web')->user() ?? auth()->guard('employee')->user();
    $setupUserName = $setupUser ? ucwords(strtolower(trim($setupUser->fname . ' ' . $setupUser->lname))) : '';
@endphp
{{--
    Notifications are REQUIRED; installing the app is OPTIONAL.

    - Until this device allows notifications, a card covers the page and can't
      be closed (signing out is the only other way out).
    - iPhone is the one exception where installing is needed: Safari only
      gives push to the app added to the Home Screen, so the required card
      starts with that step there.
    - "Install app" in the navbar (#hrisSetupNavItem in layouts/master) offers
      installing to anyone whose browser can do it; that card can be closed.
    - Browsers with no push support at all (e.g. some in-app browsers) are
      never blocked, since they could never comply.
--}}
<style>
    /* Navbar entry (optional install) */
    .hs-nav { position: relative; font-weight: 600; }

    /* Card */
    .hs-overlay {
        position: fixed; inset: 0; z-index: 20000;
        display: flex; align-items: center; justify-content: center;
        padding: 16px;
        background: rgba(11, 34, 23, .58);
        -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px);
    }
    .hs-card {
        position: relative;
        width: 100%; min-width: 0; max-width: 440px; max-height: calc(100vh - 32px); overflow-x: hidden; overflow-y: auto;
        border-radius: 20px;
        background: #fff;
        box-shadow: 0 24px 60px -12px rgba(5, 30, 18, .45);
        color: #1c2b24; font-size: 14px; line-height: 1.5;
    }
    .hs-card button { font-family: inherit; }
    .hs-close {
        position: absolute; top: 12px; right: 12px;
        width: 34px; height: 34px; border: 0; border-radius: 10px;
        background: none; color: #8a978f; font-size: 16px;
    }
    .hs-close:hover { background: #f4f7f5; color: #1c2b24; }
    .hs-close:focus-visible { outline: 3px solid #9ad3b3; }
    .hs-close[hidden] { display: none; }
    .hs-head {
        display: flex; align-items: center; gap: 10px;
        padding: 18px 56px 0 22px;
    }
    .hs-head img { width: 34px; height: 34px; border-radius: 9px; }
    .hs-brand { font-weight: 700; font-size: 14px; line-height: 1.1; }
    .hs-brand small { display: block; font-weight: 500; font-size: 12px; color: #56655d; margin-top: 2px; }

    /* Stepper (iPhone only: install, then notifications) */
    .hs-steps { display: flex; align-items: center; gap: 8px; margin: 18px 22px 0; padding: 0; list-style: none; }
    .hs-steps:empty { display: none; }
    .hs-step { display: flex; align-items: center; gap: 8px; flex: 0 1 auto; min-width: 0; font-size: 12.5px; font-weight: 600; color: #8a978f; }
    .hs-step + .hs-step { flex: 1 1 auto; }
    .hs-step + .hs-step::before { content: ''; flex: 1 1 auto; min-width: 16px; height: 2px; border-radius: 2px; background: #e4ebe7; margin-right: 2px; }
    .hs-step-dot {
        flex: 0 0 26px; height: 26px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        border: 2px solid #d5dfd9; background: #fff; font-size: 12px; font-weight: 700; color: #8a978f;
    }
    .hs-step span:last-child { min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .hs-step.is-current { color: #146a3b; }
    .hs-step.is-current .hs-step-dot { border-color: #146a3b; background: #146a3b; color: #fff; box-shadow: 0 0 0 4px #e3f2e9; }
    .hs-step.is-done { color: #146a3b; }
    .hs-step.is-done .hs-step-dot { border-color: #23955a; background: #e3f2e9; color: #146a3b; }
    .hs-step.is-done + .hs-step::before { background: #23955a; }

    .hs-body { padding: 22px 22px 8px; text-align: center; }
    .hs-icon {
        width: 72px; height: 72px; margin: 0 auto 14px; border-radius: 20px;
        display: flex; align-items: center; justify-content: center;
        font-size: 30px; color: #146a3b;
        background: linear-gradient(135deg, #e3f2e9 0%, #f2f8f4 100%);
    }
    .hs-icon.is-warn { color: #a8322a; background: linear-gradient(135deg, #fde0dd 0%, #fdf0ee 100%); }
    .hs-title { margin: 0 0 6px; font-size: 20px; font-weight: 700; letter-spacing: -.01em; }
    .hs-lead { margin: 0 auto; max-width: 340px; color: #56655d; }

    .hs-howto {
        margin: 18px 0 0; padding: 14px 16px; list-style: none; counter-reset: hs;
        text-align: left; border-radius: 12px; background: #f4f7f5;
    }
    .hs-howto li { position: relative; padding-left: 32px; counter-increment: hs; overflow-wrap: anywhere; }
    .hs-howto li + li { margin-top: 10px; }
    .hs-howto li::before {
        content: counter(hs);
        position: absolute; left: 0; top: 0;
        width: 22px; height: 22px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 11.5px; font-weight: 700; background: #fff; color: #146a3b; border: 1px solid #d9eee2;
    }
    .hs-chip {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 0 7px; height: 22px; border-radius: 6px; vertical-align: 1px;
        background: #fff; border: 1px solid #d5dfd9; font-size: 12px; font-weight: 600; white-space: nowrap;
    }

    .hs-actions { padding: 18px 22px 18px; }
    .hs-btn {
        width: 100%; height: 48px; border: 0; border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        background: #146a3b; color: #fff; font-size: 15px; font-weight: 600;
        transition: background-color .15s ease, transform .05s ease;
    }
    .hs-btn:hover { background: #0f5a32; }
    .hs-btn:active { transform: translateY(1px); }
    .hs-btn:focus-visible { outline: 3px solid #9ad3b3; outline-offset: 2px; }
    .hs-btn:disabled { opacity: .6; cursor: progress; }
    .hs-btn .hs-spin { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: hs-spin .7s linear infinite; }
    .hs-alt {
        display: block; margin: 8px auto 0; padding: 6px 10px; border: 0; background: none; border-radius: 8px;
        font-size: 13.5px; font-weight: 600; color: #56655d;
    }
    .hs-alt:hover { color: #146a3b; background: #f4f7f5; }
    .hs-alt[hidden] { display: none; }
    .hs-note { margin: 8px 0 0; font-size: 12.5px; color: #a8322a; text-align: center; }
    .hs-note:empty { display: none; }

    .hs-foot {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        padding: 12px 22px 16px; border-top: 1px solid #e4ebe7;
        font-size: 12.5px; color: #56655d;
    }
    .hs-foot[hidden] { display: none; }
    .hs-foot b { color: #1c2b24; font-weight: 600; }
    .hs-signout { border: 0; background: none; padding: 0; font-weight: 600; color: #146a3b; font-size: 12.5px; }
    .hs-signout:hover { text-decoration: underline; }

    @keyframes hs-spin { to { transform: rotate(360deg); } }
    @keyframes hs-in { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: no-preference) {
        .hs-card { animation: hs-in .28s cubic-bezier(.2, .8, .2, 1); }
    }

    /* Phones: a bottom sheet that's easy to reach with a thumb. */
    @media (max-width: 575.98px) {
        .hs-overlay { align-items: flex-end; padding: 0; }
        .hs-card {
            max-width: none; max-height: 92vh; border-radius: 20px 20px 0 0;
            padding-bottom: env(safe-area-inset-bottom);
        }
        .hs-title { font-size: 19px; }
    }
</style>
{{-- This partial sits in <head>, so the card is a template added to <body> when needed. --}}
<template id="hrisSetupTpl">
    <div class="hs-overlay" id="hrisSetup" role="dialog" aria-modal="true" aria-labelledby="hsTitle" aria-describedby="hsLead">
        <div class="hs-card">
            <button type="button" class="hs-close" id="hsClose" aria-label="Close"><i class="fas fa-times"></i></button>
            <div class="hs-head">
                <img src="{{ asset('icons/icon-192.png') }}" alt="">
                <div class="hs-brand">CPSU HRIS<small>Set up this device</small></div>
            </div>
            <ol class="hs-steps" id="hsSteps" aria-label="Setup steps"></ol>
            <div class="hs-body">
                <div class="hs-icon" id="hsIcon"><i class="fas fa-bell"></i></div>
                <h2 class="hs-title" id="hsTitle"></h2>
                <p class="hs-lead" id="hsLead"></p>
                <div id="hsHowto"></div>
            </div>
            <div class="hs-actions">
                <button type="button" class="hs-btn" id="hsBtn"></button>
                <button type="button" class="hs-alt" id="hsAlt" hidden></button>
                <p class="hs-note" id="hsNote" aria-live="polite"></p>
            </div>
            <div class="hs-foot" id="hsFoot" hidden>
                <span>Signed in as <b>{{ $setupUserName }}</b></span>
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="hs-signout">Sign out</button>
                </form>
            </div>
        </div>
    </div>
</template>
<script>
    (function () {
        var VAPID_KEY = @json(config('webpush.public_key'));
        var SUBSCRIBE_URL = @json(route('push.subscribe'));
        var UNSUBSCRIBE_URL = @json(route('push.unsubscribe'));
        var LOGOUT_PATH = new URL(@json(route('logout'))).pathname;

        // ---------- What this device / browser can do ----------
        var ua = navigator.userAgent;
        var isIos = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        var isAndroid = /Android/i.test(ua);
        var isFirefox = /Firefox|FxiOS/i.test(ua);
        var isEdge = /Edg\//.test(ua);
        var isSamsung = /SamsungBrowser/i.test(ua);
        var isChromium = !isFirefox && /Chrome|CriOS|Edg\/|SamsungBrowser|OPR\//.test(ua);
        var isMacSafari = !isIos && /Macintosh/.test(ua) && /Safari/.test(ua) && !isChromium && !isFirefox;
        var safariVersion = parseInt((ua.match(/Version\/(\d+)/) || [])[1] || '0', 10);
        var isInAppBrowser = /FBAN|FBAV|Instagram|Line\/|MicroMessenger|; wv\)/i.test(ua);
        var pushSupported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;

        function isInstalled() {
            return window.navigator.standalone === true
                || window.matchMedia('(display-mode: standalone)').matches
                || window.matchMedia('(display-mode: window-controls-overlay)').matches
                || window.matchMedia('(display-mode: minimal-ui)').matches;
        }

        var canInstall = !isInAppBrowser && (isIos || isAndroid || isChromium || (isMacSafari && safariVersion >= 17));
        // iPhone in a Safari tab: notifications only exist in the installed app.
        var iosNeedsInstall = isIos && !pushSupported && canInstall && !isInstalled();

        // ---------- Storage (can be blocked) ----------
        function remember(installed) {
            try { installed ? localStorage.setItem('hrisInstalled', '1') : localStorage.removeItem('hrisInstalled'); } catch (e) {}
        }
        function remembered() {
            try { return localStorage.getItem('hrisInstalled') === '1'; } catch (e) { return false; }
        }

        // ---------- Push subscription ----------
        function csrf() {
            var m = document.querySelector('meta[name="csrf-token"]');
            return m ? m.getAttribute('content') : '';
        }

        function post(url, body, keepalive) {
            return fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                keepalive: !!keepalive,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf()
                },
                body: JSON.stringify(body)
            });
        }

        function keyToBytes(base64) {
            var padded = (base64 + '===='.slice((base64.length % 4) || 4)).replace(/-/g, '+').replace(/_/g, '/');
            var raw = atob(padded), out = new Uint8Array(raw.length);
            for (var i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
            return out;
        }

        function payload(sub) {
            var json = sub.toJSON();
            var encodings = PushManager.supportedContentEncodings || ['aesgcm'];
            json.contentEncoding = encodings.indexOf('aes128gcm') !== -1 ? 'aes128gcm' : 'aesgcm';
            return json;
        }

        var swReg = null;

        function subscribe(reg) {
            return reg.pushManager.getSubscription().then(function (existing) {
                return existing || reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyToBytes(VAPID_KEY) });
            }).then(function (sub) {
                return post(SUBSCRIBE_URL, payload(sub)).then(function () { return sub; });
            });
        }

        function el(id) { return document.getElementById(id); }

        // ---------- Navbar "Install app" (optional) ----------
        function refreshNav() {
            var nav = el('hrisSetupNavItem');
            if (nav) nav.style.display = (canInstall && !isInstalled() && !remembered() && !iosNeedsInstall) ? '' : 'none';
        }

        // ---------- Card ----------
        var required = false;
        var lastFocus = null;

        function openCard(isRequired) {
            required = isRequired;
            if (!el('hrisSetup')) {
                lastFocus = document.activeElement;
                document.body.appendChild(el('hrisSetupTpl').content.cloneNode(true));
                document.documentElement.style.overflow = 'hidden';
                var sheet = el('hrisSetup');
                sheet.addEventListener('mousedown', function (e) { if (e.target === sheet && !required) closeCard(); });
                el('hsClose').addEventListener('click', function () { if (!required) closeCard(); });
                sheet.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && !required) return closeCard();
                    if (e.key !== 'Tab') return;
                    // Keep keyboard focus inside the card.
                    var items = sheet.querySelectorAll('button:not([disabled]):not([hidden])');
                    if (!items.length) return;
                    var first = items[0], last = items[items.length - 1];
                    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                });
            }
            el('hsClose').hidden = required;
            el('hsFoot').hidden = !required;
        }

        function closeCard() {
            var sheet = el('hrisSetup');
            if (sheet) sheet.remove();
            document.documentElement.style.overflow = '';
            refreshNav();
            if (lastFocus && lastFocus.focus) lastFocus.focus();
        }

        // screen: {required, steps, step, icon, warn, title, lead, howto[], button, onClick, alt:{label,onClick}}
        function show(screen) {
            openCard(!!screen.required);
            var steps = screen.steps || [];
            var current = steps.indexOf(screen.step);
            el('hsSteps').innerHTML = steps.map(function (label, i) {
                var state = i < current ? 'is-done' : (i === current ? 'is-current' : '');
                var dot = i < current ? '<i class="fas fa-check"></i>' : (i + 1);
                return '<li class="hs-step ' + state + '"' + (i === current ? ' aria-current="step"' : '') + '>'
                    + '<span class="hs-step-dot">' + dot + '</span><span>' + label + '</span></li>';
            }).join('');
            el('hsIcon').className = 'hs-icon' + (screen.warn ? ' is-warn' : '');
            el('hsIcon').innerHTML = '<i class="fas ' + screen.icon + '"></i>';
            el('hsTitle').textContent = screen.title;
            el('hsLead').innerHTML = screen.lead;
            el('hsHowto').innerHTML = screen.howto && screen.howto.length
                ? '<ol class="hs-howto">' + screen.howto.map(function (h) { return '<li>' + h + '</li>'; }).join('') + '</ol>'
                : '';
            var btn = el('hsBtn');
            btn.disabled = false;
            btn.innerHTML = screen.button;
            btn.onclick = screen.onClick;
            var alt = el('hsAlt');
            alt.hidden = !screen.alt;
            if (screen.alt) {
                alt.textContent = screen.alt.label;
                alt.onclick = screen.alt.onClick;
            }
            note('');
            btn.focus({ preventScroll: true });
        }

        function busy(on, label) {
            var btn = el('hsBtn');
            btn.disabled = on;
            if (on) btn.innerHTML = '<span class="hs-spin" aria-hidden="true"></span>' + label;
        }

        function note(text) { if (el('hsNote')) el('hsNote').textContent = text; }

        var chip = function (html) { return '<span class="hs-chip">' + html + '</span>'; };
        var IOS_STEPS = ['Install app', 'Allow notifications'];

        // ---------- Install (optional; required only on iPhone Safari) ----------
        function installSteps() {
            if (isIos) return [
                'Tap ' + chip('<i class="fas fa-share-square"></i> Share') + ' in Safari\'s toolbar.',
                'Scroll down and tap ' + chip('<i class="far fa-plus-square"></i> Add to Home Screen') + ', then ' + chip('Add') + '.',
                'Open <b>CPSU HRIS</b> from your Home Screen.'
            ];
            if (isAndroid && isSamsung) return [
                'Tap ' + chip('<i class="fas fa-bars"></i>') + ' at the bottom, then ' + chip('Add page to') + '.',
                'Choose ' + chip('Home screen') + ' and confirm.'
            ];
            if (isAndroid) return [
                'Tap ' + chip('<i class="fas fa-ellipsis-v"></i>') + ' at the top right.',
                'Tap ' + chip('Install app') + ' or ' + chip('Add to Home screen') + ', then ' + chip('Install') + '.'
            ];
            if (isMacSafari) return [
                'In the menu bar, choose ' + chip('File') + ' &rarr; ' + chip('Add to Dock') + '.'
            ];
            if (isEdge) return [
                'Click ' + chip('<i class="fas fa-ellipsis-h"></i>') + ' &rarr; ' + chip('Apps') + ' &rarr; ' + chip('Install this site as an app') + '.'
            ];
            return [
                'Click the install icon ' + chip('<i class="fas fa-desktop"></i>') + ' at the right end of the address bar,<br>or ' + chip('<i class="fas fa-ellipsis-v"></i>') + ' &rarr; ' + chip('Cast, save, and share') + ' &rarr; ' + chip('Install page as app') + '.'
            ];
        }

        // iPhone in Safari: required, because notifications only work in the installed app.
        function showIosInstall() {
            show({
                required: true, steps: IOS_STEPS, step: IOS_STEPS[0], icon: 'fa-mobile-alt',
                title: 'Add HRIS to your Home Screen',
                lead: 'HRIS notifications are required. On iPhone they only work from the app on your Home Screen:',
                howto: installSteps(),
                button: '<i class="fas fa-check"></i> I\'ve added it',
                onClick: function () {
                    note('Now open CPSU HRIS from its icon on your Home Screen to turn on notifications.');
                }
            });
        }

        // Everyone else: optional, from the navbar.
        function showInstall() {
            var oneTap = window.hrisPwa && window.hrisPwa.canPrompt();
            show({
                required: false, icon: 'fa-download',
                title: 'Install the HRIS app',
                lead: 'Optional: open HRIS like any other app, straight from your home screen or desktop.'
                    + (oneTap ? '' : ' Here\'s how:'),
                howto: oneTap ? [] : installSteps(),
                button: oneTap ? '<i class="fas fa-download"></i> Install CPSU HRIS' : '<i class="fas fa-check"></i> Done',
                onClick: function () {
                    if (window.hrisPwa && window.hrisPwa.canPrompt()) {
                        busy(true, 'Installing&hellip;');
                        window.hrisPwa.prompt().then(function (outcome) {
                            if (outcome === 'accepted') {
                                remember(true);
                                closeCard();
                                if (window.toastr) toastr.success('CPSU HRIS is installed on this device.');
                            } else {
                                showInstall();
                            }
                        });
                        return;
                    }
                    remember(true);
                    closeCard();
                },
                alt: { label: 'Not now', onClick: closeCard }
            });
        }

        // ---------- Notifications (required) ----------
        function notifySteps() { return isIos ? IOS_STEPS : null; }

        function showNotify() {
            show({
                required: true, steps: notifySteps(), step: IOS_STEPS[1], icon: 'fa-bell',
                title: 'Turn on notifications',
                lead: 'HRIS notifications are required. You\'ll be alerted right away when your leave or CTO moves, and when something is waiting for your approval.',
                button: '<i class="fas fa-bell"></i> Allow notifications',
                onClick: askPermission
            });
        }

        function blockedSteps() {
            if (isIos) return ['Open iPhone ' + chip('Settings') + ' &rarr; ' + chip('Notifications') + '.', 'Tap <b>CPSU HRIS</b> and turn on ' + chip('Allow Notifications') + '.', 'Come back and tap <b>Check again</b>.'];
            if (isAndroid && isInstalled()) return ['Long-press the <b>CPSU HRIS</b> icon &rarr; ' + chip('<i class="fas fa-info-circle"></i> App info') + ' &rarr; ' + chip('Notifications') + '.', 'Turn notifications <b>on</b>.', 'Come back and tap <b>Check again</b>.'];
            if (isAndroid) return ['Tap ' + chip('<i class="fas fa-ellipsis-v"></i>') + ' &rarr; ' + chip('Settings') + ' &rarr; ' + chip('Site settings') + ' &rarr; ' + chip('Notifications') + '.', 'Set this site to <b>Allow</b>.', 'Come back and tap <b>Check again</b>.'];
            return ['Click ' + chip('<i class="fas fa-sliders-h"></i>') + ' or ' + chip('<i class="fas fa-lock"></i>') + ' at the left of the address bar.', 'Set ' + chip('Notifications') + ' to <b>Allow</b>.', 'Click <b>Check again</b>.'];
        }

        function showBlocked() {
            show({
                required: true, steps: notifySteps(), step: IOS_STEPS[1], icon: 'fa-bell-slash', warn: true,
                title: 'Notifications are blocked',
                lead: 'HRIS notifications are required, but this device is blocking them. To turn them on:',
                howto: blockedSteps(),
                button: '<i class="fas fa-redo"></i> Check again',
                onClick: function () {
                    if (Notification.permission === 'denied') {
                        // Settings changes often need a reload to be seen.
                        busy(true, 'Checking&hellip;');
                        location.reload();
                        return;
                    }
                    requireNotifications();
                }
            });
        }

        function askPermission() {
            busy(true, 'Waiting for your answer&hellip;');
            Promise.resolve(Notification.requestPermission()).then(function (permission) {
                if (permission === 'granted') {
                    busy(true, 'Finishing setup&hellip;');
                    return (swReg ? subscribe(swReg) : Promise.reject()).then(function () {
                        closeCard();
                        if (window.toastr) toastr.success('All set! You\'ll get HRIS notifications on this device.');
                    }, function () {
                        showNotify();
                        note('Notifications are allowed, but this device could not be linked. Please try again.');
                    });
                }
                if (permission === 'denied') return showBlocked();
                showNotify();
                note('Choose "Allow" when your browser asks.');
            }).catch(function () {
                showNotify();
                note('Something went wrong. Please try again.');
            });
        }

        function requireNotifications() {
            if (Notification.permission === 'granted') return closeCard();
            return Notification.permission === 'denied' ? showBlocked() : showNotify();
        }

        if (isInstalled()) remember(true);

        document.addEventListener('DOMContentLoaded', function () {
            // Navbar "Install app" opens the optional install card.
            document.addEventListener('click', function (e) {
                if (e.target.closest('#hrisSetupBtn')) {
                    e.preventDefault();
                    if (!required) showInstall();
                }
            });
            // The browser only offers installing when the app isn't installed.
            document.addEventListener('hris:installable', function () { remember(false); refreshNav(); });
            document.addEventListener('hris:installed', function () { remember(true); refreshNav(); });

            refreshNav();

            if (iosNeedsInstall) return showIosInstall();
            if (!pushSupported) return; // can't receive push here at all, so never block

            navigator.serviceWorker.ready.then(function (reg) {
                swReg = reg;
                if (Notification.permission === 'granted') {
                    // Re-link this device to the account now signed in (shared PCs, key changes).
                    subscribe(reg).catch(function () {});
                } else {
                    requireNotifications();
                }

                // Signing out stops this device getting the account's alerts.
                document.addEventListener('submit', function (e) {
                    var form = e.target;
                    if (!form || !form.action || form.dataset.pushCleared || new URL(form.action, location.href).pathname !== LOGOUT_PATH) return;
                    e.preventDefault();
                    form.dataset.pushCleared = '1';

                    var done = function () { form.submit(); };
                    var timer = setTimeout(done, 1500);

                    reg.pushManager.getSubscription().then(function (sub) {
                        if (!sub) return;
                        return post(UNSUBSCRIBE_URL, { endpoint: sub.endpoint }, true).then(function () { return sub.unsubscribe(); });
                    }).catch(function () {}).then(function () {
                        clearTimeout(timer);
                        done();
                    });
                }, true);
            });
        });
    })();
</script>
@endif
