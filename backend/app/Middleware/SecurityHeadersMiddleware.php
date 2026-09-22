<?php

declare(strict_types=1);

namespace Kamelya\Middleware;

use Kamelya\Core\Config;
use Kamelya\Core\Request;

/** Güvenlik başlıkları — standart §4. HSTS yalnızca HTTPS'te gönderilir. */
final class SecurityHeadersMiddleware
{
    public function isle(Request $istek, callable $sonraki): void
    {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net fonts.googleapis.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src fonts.gstatic.com; img-src 'self' data:; connect-src 'self'");
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        $https = ($_SERVER['HTTPS'] ?? '') === 'on'
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        if ($https) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // Sürüm sızıntısını kapat.
        header_remove('X-Powered-By');

        $sonraki($istek);
    }
}
