<?php

declare(strict_types=1);

namespace Kamelya\Core;

/** Hassas veri maskeleme — ham IP loglara/y anıtlara yazılmaz. */
final class Gizlilik
{
    public static function ipMaskele(mixed $ip): ?string
    {
        if (!is_string($ip) || $ip === '') {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $parca = explode('.', $ip);

            return $parca[0] . '.' . $parca[1] . '.' . $parca[2] . '.***';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            $parca = explode(':', rtrim($ip, ':'));

            return implode(':', array_slice($parca, 0, 3)) . ':****';
        }

        return '***';
    }
}
