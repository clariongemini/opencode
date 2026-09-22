<?php

declare(strict_types=1);

namespace Kamelya\Core;

/**
 * HS256 JWT — admin panel kimlik doğrulama.
 * Claim'ler: iss, sub (kullanici id), rol, tip (access|refresh), iat, exp, jti.
 * Gizli anahtar yalnızca env (JWT_GIZLI_ANAHTAR, min 16 karakter).
 */
final class Jwt
{
    private const ALGORITMA = 'HS256';

    public static function uret(int $kullaniciId, string $rol, int $saniye, string $tip = 'access'): string
    {
        $simdi = time();
        $baslik = self::kodla(['alg' => self::ALGORITMA, 'typ' => 'JWT']);
        $yuk = self::kodla([
            'iss' => 'kamelya',
            'sub' => $kullaniciId,
            'rol' => $rol,
            'tip' => $tip,
            'iat' => $simdi,
            'exp' => $simdi + $saniye,
            'jti' => bin2hex(random_bytes(16)),
        ]);
        $imza = self::kodlaHam(hash_hmac('sha256', $baslik . '.' . $yuk, self::gizliAnahtar(), true));

        return $baslik . '.' . $yuk . '.' . $imza;
    }

    /** Geçerliyse yükü döner, değilse null (imza/algoritma/süre). */
    /** @return array<string, mixed>|null */
    public static function dogrula(string $jeton): ?array
    {
        $parca = explode('.', $jeton);
        if (count($parca) !== 3) {
            return null;
        }

        [$baslik, $yuk, $imza] = $parca;
        $baslikCozum = json_decode(self::coz($baslik), true);
        if (!is_array($baslikCozum) || ($baslikCozum['alg'] ?? '') !== self::ALGORITMA) {
            return null;
        }

        $beklenen = hash_hmac('sha256', $baslik . '.' . $yuk, self::gizliAnahtar(), true);
        if (!hash_equals(self::kodlaHam($beklenen), $imza)) {
            return null;
        }

        $yukCozum = json_decode(self::coz($yuk), true);
        if (!is_array($yukCozum) || !isset($yukCozum['exp'], $yukCozum['sub']) || (int) $yukCozum['exp'] <= time()) {
            return null;
        }

        return $yukCozum;
    }

    /** @param array<string, mixed> $veri */
    private static function kodla(array $veri): string
    {
        return self::kodlaHam((string) json_encode($veri, JSON_UNESCAPED_UNICODE));
    }

    private static function kodlaHam(string $ham): string
    {
        return rtrim(strtr(base64_encode($ham), '+/', '-_'), '=');
    }

    private static function coz(string $kodlu): string
    {
        $duz = strtr($kodlu, '-_', '+/');
        $kalan = strlen($duz) % 4;
        if ($kalan > 0) {
            $duz .= str_repeat('=', 4 - $kalan);
        }

        $cozum = base64_decode($duz, true);

        return $cozum === false ? '' : $cozum;
    }

    private static function gizliAnahtar(): string
    {
        $anahtar = (string) Config::cev('JWT_GIZLI_ANAHTAR', '');
        if (strlen($anahtar) < 16) {
            throw new \RuntimeException('JWT gizli anahtarı tanımsız (min 16 karakter).');
        }

        return $anahtar;
    }
}
