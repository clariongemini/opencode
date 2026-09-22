<?php

declare(strict_types=1);

/** Ortak önyükleme: config + dil + çeviri + yardımcılar. Router tarafından çağrılır. */

$AYAR = require __DIR__ . '/../config.php';

function cozumlenebilirDil(string $kod, array $ayar): ?string
{
    return in_array($kod, $ayar['diller'], true) ? $kod : null;
}

// Router $dil ve $sorguLang değerlerini önceden belirler.
$dil = $dil ?? $AYAR['varsayilan_dil'];
$CEVIRI = require __DIR__ . '/../lang/' . $dil . '.php';

function t(string $anahtar): string
{
    global $CEVIRI;

    return $CEVIRI[$anahtar] ?? $anahtar;
}

function siteUrl(string $yol = ''): string
{
    global $AYAR, $dil;
    $taban = rtrim($AYAR['site_taban'], '/');
    $onek = $dil === 'tr' ? '' : '/' . $dil;

    return $taban . $onek . $yol;
}

function dilYonu(): string
{
    global $AYAR, $dil;

    return in_array($dil, $AYAR['rtl_diller'], true) ? 'rtl' : 'ltr';
}

function mevcutYol(): string
{
    global $MEVCUT_YOL;

    return $MEVCUT_YOL ?? '/';
}

/** Mevcut sayfanın başka dildeki karşılığı. */
function dilUrl(string $hedefDil): string
{
    global $AYAR;
    $taban = rtrim($AYAR['site_taban'], '/');
    $onek = $hedefDil === 'tr' ? '' : '/' . $hedefDil;

    return $taban . $onek . mevcutYol();
}

function kisaAciklama(string $metin, int $uzunluk = 150): string
{
    $temiz = trim(preg_replace('/\s+/', ' ', strip_tags($metin)) ?? '');
    if (mb_strlen($temiz) <= $uzunluk) {
        return $temiz;
    }

    return mb_substr($temiz, 0, $uzunluk - 1) . '…';
}
