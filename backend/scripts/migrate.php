#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Kullanım: php scripts/migrate.php [up|down|durum]
 * - up: bekleyen migration'ları sırayla uygular (her biri transaction içinde).
 * - down: en son uygulananı geri alır.
 * - durum: uygulanan/bekleyen listesi.
 */

use Kamelya\Core\Config;
use Kamelya\Core\Database;

$kokuDizin = dirname(__DIR__);
require $kokuDizin . '/vendor/autoload.php';
Config::yukle($kokuDizin);

$eylem = $argv[1] ?? 'up';
$dizin = $kokuDizin . '/database/migrations';
$baglanti = Database::baglanti();

$baglanti->exec('CREATE TABLE IF NOT EXISTS gocler (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dosya VARCHAR(190) NOT NULL UNIQUE,
    calistirildi_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

$calisanlar = $baglanti->query('SELECT dosya FROM gocler ORDER BY id')->fetchAll(PDO::FETCH_COLUMN) ?: [];
$dosyalar = glob($dizin . '/*.php') ?: [];
sort($dosyalar);

if ($eylem === 'durum') {
    foreach ($dosyalar as $yol) {
        $ad = basename($yol);
        $durum = in_array($ad, $calisanlar, true) ? 'UYGULANDI' : 'BEKLIYOR';
        echo "{$durum}  {$ad}\n";
    }

    exit(0);
}

if ($eylem === 'down') {
    $son = end($calisanlar);
    if ($son === false) {
        echo "Geri alınacak migration yok.\n";

        exit(0);
    }

    $goc = require $dizin . '/' . $son;
    $baglanti->beginTransaction();
    try {
        $goc->down($baglanti);
        // NOT: DDL (DROP TABLE) MySQL'de örtük commit yapar; iz kaydı ayrı yazılır.
        $ifade = $baglanti->prepare('DELETE FROM gocler WHERE dosya = ?');
        $ifade->execute([$son]);
        if ($baglanti->inTransaction()) {
            $baglanti->commit();
        }

        echo "Geri alındı: {$son}\n";
    } catch (Throwable $hata) {
        if ($baglanti->inTransaction()) {
            $baglanti->rollBack();
        }

        fwrite(STDERR, "HATA {$son}: " . $hata->getMessage() . "\n");

        exit(1);
    }

    exit(0);
}

if ($eylem !== 'up') {
    fwrite(STDERR, "Bilinmeyen eylem: {$eylem} (up|down|durum)\n");

    exit(1);
}

$sayac = 0;
foreach ($dosyalar as $yol) {
    $ad = basename($yol);
    if (in_array($ad, $calisanlar, true)) {
        continue;
    }

    $goc = require $yol;
    $baglanti->beginTransaction();
    try {
        $goc->up($baglanti);
        // NOT: DDL (CREATE TABLE) MySQL'de örtük commit yapar; iz kaydı ayrı yazılır.
        $ifade = $baglanti->prepare('INSERT INTO gocler (dosya) VALUES (?)');
        $ifade->execute([$ad]);
        if ($baglanti->inTransaction()) {
            $baglanti->commit();
        }

        echo "Uygulandı: {$ad}\n";
        $sayac++;
    } catch (Throwable $hata) {
        if ($baglanti->inTransaction()) {
            $baglanti->rollBack();
        }

        fwrite(STDERR, "HATA {$ad}: " . $hata->getMessage() . "\n");

        exit(1);
    }
}

echo "Tamam: {$sayac} migration uygulandı.\n";
