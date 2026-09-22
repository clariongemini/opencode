<?php

declare(strict_types=1);

namespace Kamelya\Core;

/**
 * CSRF koruması (admin yazma uçları, F7 CRUD).
 * JWT jti-bazlı: token = HMAC(csrf:jti, gizli anahtar). Session yok (API stateless).
 * İstemci: login yanıtındaki csrf_token'ı `X-CSRF-Token` başlığıyla gönderir.
 */
final class Csrf
{
    public static function tokenUret(string $jti): string
    {
        $ham = hash_hmac('sha256', 'csrf:' . $jti, (string) Config::cev('JWT_GIZLI_ANAHTAR', ''), true);

        return rtrim(strtr(base64_encode($ham), '+/', '-_'), '=');
    }

    public static function dogrula(mixed $jeton, string $jti): bool
    {
        if (!is_string($jeton) || $jeton === '' || $jti === '') {
            return false;
        }

        return hash_equals(self::tokenUret($jti), $jeton);
    }
}
