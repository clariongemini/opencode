<?php

declare(strict_types=1);

namespace Kamelya\Middleware;

use Kamelya\Core\Config;
use Kamelya\Core\Request;

/** CORS allowlist — .env CORS_ORIGINS (virgüllü). '*' yalnızca geliştirme. */
final class CorsMiddleware
{
    public function isle(Request $istek, callable $sonraki): void
    {
        $izinliler = array_map('trim', explode(',', (string) Config::al('app.cors_kokenler', '*')));
        $koken = $istek->baslik('Origin') ?? '';

        if (in_array('*', $izinliler, true)) {
            header('Access-Control-Allow-Origin: *');
        } elseif ($koken !== '' && in_array($koken, $izinliler, true)) {
            header('Access-Control-Allow-Origin: ' . $koken);
            header('Vary: Origin');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

        if ($istek->metot === 'OPTIONS') {
            http_response_code(204);

            return;
        }

        $sonraki($istek);
    }
}
