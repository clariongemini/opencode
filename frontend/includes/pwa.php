<?php

declare(strict_types=1);

/** PWA bağlantısı — manifest + Service Worker kaydı (layout head/body). */

function pwaKafa(): string
{
    return '<link rel="manifest" href="/manifest.json">'
        . "\n    " . '<meta name="theme-color" content="#2F6B3C">'
        . "\n    " . '<link rel="apple-touch-icon" href="/assets/img/ikon-192.png">';
}

function pwaGovde(): string
{
    return <<<HTML
    <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function () {
        navigator.serviceWorker.register('/service-worker.js').catch(function () {});
      });
    }
    </script>
    HTML;
}
