<?php

declare(strict_types=1);

namespace Kamelya\Middleware;

use Kamelya\Core\Config;
use Kamelya\Core\Request;

/** Production HTTPS zorunluluğu (FORCE_HTTPS=true iken 301). Yerelde etkisiz. */
final class HttpsMiddleware
{
    public function isle(Request $istek, callable $sonraki): void
    {
        $zorunlu = filter_var(Config::cev('FORCE_HTTPS', 'false'), FILTER_VALIDATE_BOOLEAN);
        $https = ($_SERVER['HTTPS'] ?? '') === 'on'
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        if ($zorunlu && !$https) {
            $sunucu = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $yol = (string) ($_SERVER['REQUEST_URI'] ?? '/');
            header('Location: https://' . $sunucu . $yol, true, 301);

            return;
        }

        $sonraki($istek);
    }
}
