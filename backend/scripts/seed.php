#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Kullanım: php scripts/seed.php
 * Deterministik seed — production'da çalıştırılmaz.
 */

use Kamelya\Core\Config;
use Kamelya\Core\Database;
use Kamelya\Seeders\DatabaseSeeder;

$kokuDizin = dirname(__DIR__);
require $kokuDizin . '/vendor/autoload.php';
Config::yukle($kokuDizin);

if (Config::cev('APP_ENV', 'local') === 'production') {
    fwrite(STDERR, "Seed production ortamında yasak.\n");

    exit(1);
}

// Seeder sınıfları composer autoload dışında tutulur (app/ dışı).
require $kokuDizin . '/database/seeders/KategoriSeeder.php';
require $kokuDizin . '/database/seeders/KategoriCeviriSeeder.php';
require $kokuDizin . '/database/seeders/FiyatCarpaniSeeder.php';
require $kokuDizin . '/database/seeders/UrunFiyatiSeeder.php';
require $kokuDizin . '/database/seeders/KullaniciSeeder.php';
require $kokuDizin . '/database/seeders/DatabaseSeeder.php';

try {
    $toplam = DatabaseSeeder::calistir(Database::baglanti());
    echo "Tamam: toplam {$toplam} satır seedlendi.\n";
} catch (Throwable $hata) {
    fwrite(STDERR, 'HATA: ' . $hata->getMessage() . "\n");

    exit(1);
}
