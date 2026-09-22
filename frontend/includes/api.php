<?php

declare(strict_types=1);

/** Backend API istemcisi (cURL) — başarısızlıkta null döner, sayfa düşmez. */

function apiGet(string $uc, string $dil, array $sorgu = []): ?array
{
    global $AYAR;
    $sorgu['lang'] = $dil;
    $url = rtrim($AYAR['api_taban'], '/') . $uc . '?' . http_build_query($sorgu);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $ham = curl_exec($ch);
    curl_close($ch);

    if (!is_string($ham) || $ham === '') {
        return null;
    }

    $cozum = json_decode($ham, true);

    return is_array($cozum) && ($cozum['success'] ?? false) === true ? $cozum : null;
}

function apiPost(string $uc, array $veri): array
{
    global $AYAR;
    $url = rtrim($AYAR['api_taban'], '/') . $uc;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($veri, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
    ]);
    $ham = curl_exec($ch);
    $kod = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $cozum = is_string($ham) ? json_decode($ham, true) : null;

    return ['kod' => $kod, 'govde' => is_array($cozum) ? $cozum : null];
}
