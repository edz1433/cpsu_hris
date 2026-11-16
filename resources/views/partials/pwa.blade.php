<!-- Installable app (PWA) -->
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="theme-color" content="#146a3b">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<script>
    (function () {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(function () {});
            });
        }

        // "Install App" navbar button (#pwaInstallNavItem in layouts/master).
        // Browsers fire beforeinstallprompt only when the app is installable and not yet installed.
        var deferredPrompt = null;

        function toggleButton(show) {
            var item = document.getElementById('pwaInstallNavItem');
            if (item) item.style.display = show ? '' : 'none';
        }

        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            deferredPrompt = e;
            toggleButton(true);
        });

        window.addEventListener('appinstalled', function () {
            deferredPrompt = null;
            toggleButton(false);
        });

        document.addEventListener('DOMContentLoaded', function () {
            toggleButton(!!deferredPrompt);

            var btn = document.getElementById('pwaInstallBtn');
            if (!btn) return;
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                deferredPrompt.userChoice.finally(function () {
                    deferredPrompt = null;
                    toggleButton(false);
                });
            });
        });
    })();
</script>
