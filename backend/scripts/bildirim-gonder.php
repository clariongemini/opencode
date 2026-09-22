#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Kullanım: php scripts/bildirim-gonder.php [--limit=20]
 * Kuyruktaki bekleyen e-postaları SMTP ile gönderir (idempotent değil, durumlu).
 * Cron önerisi (5 dakikada bir — sunucuda geliştirici kurar):
 *   *\/5 * * * * /usr/bin/php /srv/kamelya/backend/scripts/bildirim-gonder.php --limit=20
 */

use Kamelya\Core\Config;
use Kamelya\Core\Database;
use Kamelya\Repositories\Admin\AyarYonetimRepository;
use Kamelya\Repositories\BildirimRepository;
use Kamelya\Services\BildirimService;

$kokuDizin = dirname(__DIR__);
require $kokuDizin . '/vendor/autoload.php';
Config::yukle($kokuDizin);

$limit = 20;
foreach ($argv as $arguman) {
    if (str_starts_with($arguman, '--limit=')) {
        $limit = max(1, min(100, (int) substr($arguman, 8)));
    }
}

$pdo = Database::baglanti();
$service = new BildirimService($pdo, new BildirimRepository($pdo), new AyarYonetimRepository($pdo));

try {
    $sonuc = $service->gonderBekleyenler($limit);
    echo "Tamam: {$sonuc['gonderildi']} gönderildi, {$sonuc['hata']} hatalı.\n";
} catch (Throwable $hata) {
    fwrite(STDERR, 'HATA: ' . $hata->getMessage() . "\n");

    exit(1);
}
