<?php

declare(strict_types=1);

namespace Kamelya\Services\Google;

use Kamelya\Core\Hata;

/**
 * Service Account bağlantısı — bağımsız istemci (curl + openssl, harici paket yok).
 * Akış: SA JSON → RS256 JWT → OAuth2 token → searchAnalytics.query.
 */
final class GscBaglanti
{
    public function __construct(private string $saYolu)
    {
    }

    public function jetonAl(): string
    {
        $ham = @file_get_contents($this->saYolu);
        $sa = is_string($ham) ? json_decode($ham, true) : null;
        if (!is_array($sa) || !isset($sa['client_email'], $sa['private_key'], $sa['token_uri'])) {
            throw new Hata('GSC_YAPILANDIRILMADI', 'Service Account dosyası geçersiz.', [], 503);
        }

        $simdi = time();
        $a = self::kodla(['alg' => 'RS256', 'typ' => 'JWT']);
        $b = self::kodla([
            'iss' => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
            'aud' => $sa['token_uri'],
            'iat' => $simdi,
            'exp' => $simdi + 3600,
        ]);

        $imza = '';
        if (!openssl_sign($a . '.' . $b, $imza, (string) $sa['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new Hata('GSC_HATA', 'JWT imzalanamadı.', [], 503);
        }

        $yanit = $this->post((string) $sa['token_uri'], http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $a . '.' . $b . '.' . self::kodlaHam($imza),
        ]), [], 'application/x-www-form-urlencoded');

        if (!isset($yanit['access_token'])) {
            throw new Hata('GSC_YETKI', 'OAuth token alınamadı.', [], 403);
        }

        return (string) $yanit['access_token'];
    }

    /** @return array<string, mixed> */
    public function sorgula(string $site, string $jeton, array $govde): array
    {
        $url = 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($site) . '/searchAnalytics/query';

        return $this->post($url, (string) json_encode($govde, JSON_UNESCAPED_UNICODE), [
            'Authorization: Bearer ' . $jeton,
        ], 'application/json');
    }

    /** @return array<string, mixed> */
    private function post(string $url, string $govde, array $basliklar, string $icerikTipi): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $govde,
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: ' . $icerikTipi, 'Accept: application/json'], $basliklar),
        ]);
        $ham = curl_exec($ch);
        $kod = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $cozum = is_string($ham) ? json_decode($ham, true) : null;
        if ($kod === 429) {
            throw new Hata('GSC_LIMIT', 'Search Console kotası aşıldı, sonra tekrar deneyin.', [], 429);
        }

        if ($kod === 403 || $kod === 401) {
            throw new Hata('GSC_YETKI', 'Service Account yetkisi yok (Search Console erişimi verin).', [], 403);
        }

        if ($kod < 200 || $kod >= 300 || !is_array($cozum)) {
            throw new Hata('GSC_HATA', 'Search Console erişim hatası (HTTP ' . $kod . ').', [], 503);
        }

        return $cozum;
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
}
