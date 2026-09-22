<?php

declare(strict_types=1);

use Kamelya\Core\Config;

return [
    'surucu' => 'mysql',
    'sunucu' => Config::cev('DB_HOST', '127.0.0.1'),
    'port' => (int) Config::cev('DB_PORT', '3306'),
    'adi' => Config::cev('DB_ADI', 'kamelya'),
    'kullanici' => Config::cev('DB_KULLANICI', 'kamelya'),
    'sifre' => Config::cev('DB_SIFRE', ''),
    'karakter' => 'utf8mb4',
];
