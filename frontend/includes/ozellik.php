<?php

declare(strict_types=1);

/**
 * Özellik kapısı (frontend) — backend etkin haritası + 1 saat dosya-önbellek.
 * Önbellek yoksa/API erişilemezse güvenli varsayılan: AÇIK (fail-open; 410 yanlışlıkla kapanmaz).
 */

function ozellikHarita(): array
{
    $dosya = sys_get_temp_dir() . '/kamelya_ozellik.json';
    if (is_file($dosya)) {
        // Ön yüz TTL'si kısa tutulur (60 sn): kapatma hızlı yayılır, yük ihmal edilir.
        $ham = json_decode((string) file_get_contents($dosya), true);
        if (is_array($ham) && isset($ham['veri']) && is_array($ham['veri'])
            && ($ham['zaman'] ?? 0) + 60 > time()) {
            return $ham['veri'];
        }
    }

    global $AYAR;
    $url = rtrim($AYAR['api_taban'], '/') . '/ozellikler';
    $baglam = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
    $ham = @file_get_contents($url, false, $baglam);
    $cozum = is_string($ham) ? json_decode($ham, true) : null;
    if (!is_array($cozum) || ($cozum['success'] ?? false) !== true || !isset($cozum['data'])) {
        return [];
    }

    file_put_contents($dosya, json_encode(['zaman' => time(), 'veri' => $cozum['data']]), LOCK_EX);

    return $cozum['data'];
}

function etkinMi(string $anahtar): bool
{
    $harita = ozellikHarita();
    if ($harita === []) {
        return true;
    }

    return ((int) ($harita[$anahtar] ?? 0)) === 1;
}
