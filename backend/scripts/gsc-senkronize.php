#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Kullanım: php scripts/gsc-senkronize.php [--gun=30]
 * Son N günün GSC verisini çekip seo_analitik_verileri'ne UPSERT eder (idempotent).
 * Cron önerisi (günlük 03:00 — sunucuda geliştirici kurar):
 *   0 3 * * * /usr/bin/php /srv/kamelya/backend/scripts/gsc-senkronize.php --gun=30
 */

use Kamelya\Core\Config;
use Kamelya\Core\Database;
use Kamelya\Repositories\SeoAnalitikRepository;
use Kamelya\Services\Google\GscService;
use Kamelya\Core\Hata;

$kokuDizin = dirname(__DIR__);
require $kokuDizin . '/vendor/autoload.php';
Config::yukle($kokuDizin);

$gun = 30;
foreach ($argv as $arguman) {
    if (str_starts_with($arguman, '--gun=')) {
        $gun = max(1, min(90, (int) substr($arguman, 6)));
    }
}

$bitis = date('Y-m-d', strtotime('-3 days')); // GSC verisi ~3 gün gecikmeli.
$baslangic = date('Y-m-d', strtotime("-{$gun} days", strtotime($bitis)));

try {
    $gsc = new GscService();
    $gsc->yapilandirmaKontrol();

    $depo = new SeoAnalitikRepository(Database::baglanti());
    $toplam = 0;

    $eslesme = [
        'sorgu' => $gsc->sorgularGetir($baslangic, $bitis),
        'sayfa' => $gsc->sayfalarGetir($baslangic, $bitis),
        'ulke' => $gsc->ulkeDagilimi($baslangic, $bitis),
        'cihaz' => $gsc->cihazDagilimi($baslangic, $bitis),
    ];

    foreach ($eslesme as $tip => $satirlar) {
        foreach ($satirlar as $satir) {
            $depo->kaydet($tip, $bitis, $satir['boyut'], $satir['tiklama'], $satir['gosterim'], $satir['ctr'], $satir['pozisyon']);
            $toplam++;
        }
    }

    foreach ($gsc->gunlukTrend($baslangic, $bitis) as $gunSatir) {
        // Trend boyutu tarih taşınır (NULL-UNIQUE tuzağından kaçınmak için).
        $tarih = substr((string) ($gunSatir['boyut'] ?? ''), 0, 10);
        if ($tarih === '') {
            continue;
        }

        $depo->kaydet('trend', $tarih, 'gunluk', $gunSatir['tiklama'], $gunSatir['gosterim'], $gunSatir['ctr'], $gunSatir['pozisyon']);
        $toplam++;
    }

    echo "Tamam: {$toplam} satır senkronize edildi ({$baslangic} – {$bitis}).\n";
} catch (Hata $hata) {
    fwrite(STDERR, 'HATA [' . $hata->hataKodu . ']: ' . $hata->getMessage() . "\n");

    exit(1);
} catch (Throwable $hata) {
    fwrite(STDERR, 'HATA: ' . $hata->getMessage() . "\n");

    exit(1);
}
